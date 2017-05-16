<?php

/**
 * Created by PhpStorm.
 * User: CaguCT
 * Date: 16.05.17
 * Time: 15:41
 */
class Sitemap
{
    /**
     * @var array
     */
    public $sitemap_array;
    /**
     * @var string
     */
    private $sitemap_filename = 'sitemap.xml';
    /**
     * @var string
     */
    private $posts = 'posts';
    /**
     * @var string
     */
    private $catalog = 'catalog';
    /**
     * @var string
     */
    private $orders = 'orders';
    /**
     * @var string
     */
    private $pano = 'pano';
    /**
     * @var string
     */
    private $ads = 'ads';

    /**
     * Sitemap constructor.
     */
    function __construct()
    {
        // Массив доступных модулей
        $this->sitemap_array = array(
            $this->posts,
            $this->catalog,
            $this->orders,
            $this->pano,
            $this->ads,
        );
    }

    /**
     * @param $home
     * Достаем список ключевых карт сайта (https://360.zp.ua/sitemap.xml)
     *
     * @return mixed
     */
    public function getSitemapList($home)
    {
        $return_array = array();

        // Проходимся по массиву модулей
        foreach( $this->sitemap_array as $item )
        {
            // Собираем массив для вывода через шаблон. Ссылка, дата последнего изменения, приоритет
            $return_array[$item] = array(
                'loc' => $home . '/' . $this->sitemap_filename . '?type=' . $item,
                'lastmod' => $this->getLastmodByName($item),
                'priority' => 0.8,
            );
        }

        return $return_array;
    }

    /**
     * @param $mod
     * Подготовка списка для карты <url>list</url>
     *
     * @return array
     */
    public function getList($mod)
    {
        $return_array = array();

        $and = $this->get_and($mod); // меняем sql запрос в зависимости от модуля

        $list = mysqlRow(DB_PREFIX . $mod, $and . ' ORDER by id DESC');
        foreach( $list as $id => $item )
        {
            // Собираем массив для вывода через шаблон. Ссылка, дата последнего изменения, приоритет
            $return_array[$id] = array(
                'loc' => $this->get_loc($mod, $item), // меняем ссылку на элемент в зависимости от модуля
                'lastmod' => $this->date($item), // выставляем дату последнего изменения
                'priority' => 0.8,
            );
        }

        return $return_array;
    }

    /**
     * Выводим категории из всех модулей (https://360.zp.ua/sitemap.xml?type=other)
     * @return array
     */
    public function getOtherList()
    {
        // вывод списка категорий
        return array_merge($this->getCategory($this->catalog . '_cat'), $this->getCategory($this->posts . '_cat'), $this->getCategory($this->pano . '_cat'), $this->getCategory($this->ads . '_cat'));
    }

    /**
     * @param $mod
     * Подготовка отдельной категории
     *
     * @return array
     */
    private function getCategory($mod)
    {
        $return_array = array();

        $sql = mysqlRow(DB_PREFIX . $mod, '1=1');

        foreach( $sql as $item )
        {
            // Собираем массив для вывода через шаблон. Ссылка, дата последнего изменения, приоритет
            $return_array[] = array(
                'loc' => $this->get_loc($mod, $item),
                'lastmod' => $this->date($item),
                'priority' => 0.6,
            );
        }

        return $return_array;
    }

    /**
     * @param $item
     * Проверяем наличие даты изменения и выводим в формате Y-m-d
     *
     * @return false|string
     */
    private function date($item)
    {
        if( !empty($item['edit_date']) && $item['edit_date'] !== '0000-00-00 00:00:00' )
            return date("Y-m-d", strtotime($item['edit_date']));
        elseif( $item['date'] !== '0000-00-00 00:00:00' )
            return date("Y-m-d", strtotime($item['date']));
        else
            return date("Y-m-d");
    }

    /**
     * @param $mod
     * @param $item
     * Выводит ссылку на элемент
     *
     * @return string
     */
    private function get_loc($mod, $item)
    {
        global $set; // вытаскиваем настройки сайта, чтоб вытащить ссылку на главную страницу ($set['host'])

        // Дефолт
        $return = '/';

        if( $mod === $this->posts )
        {
            $return = '/' . $item['id'] . '-' . $item['alt_title'] . '.html';
        }
        elseif( $mod === $this->catalog || $mod === $this->ads )
        {
            $return = '/' . $mod . '/' . $item['alt_title'] . '.html';
        }
        elseif( $mod === $this->orders )
        {
            $return = '/' . $this->orders . '/' . $item['id'] . '/';
        }
        elseif( $mod === $this->pano )
        {
            $return = '/' . $item['id'] . '-' . $this->pano . '.html';
        }
        elseif( $mod === $this->catalog . '_cat' )
        {
            $return = '/' . $this->catalog . '/category/' . $item['alt_name'] . '/';
        }
        elseif( $mod === $this->posts . '_cat' )
        {
            $return = '/' . $this->posts . '/' . $item['alt_name'] . '/';
        }
        elseif( $mod === $this->pano . '_cat' )
        {
            $return = '/' . $this->pano . '/category/' . $item['alt_name'] . '/';
        }
        elseif( $mod === $this->ads . '_cat' )
        {
            $return = '/' . $this->ads . '/category/' . $item['alt_name'] . '/';
        }

        return $set['host'] . htmlspecialchars($return);
    }

    /**
     * @param $mod
     * Меняем sql запрос в зависимости от модуля
     *
     * @return string
     */
    private function get_and($mod)
    {
        // Дефолт (большинство модулей)
        $return = 'publish = 1';

        if( $mod === $this->ads )
        {
            $return = '`activate` = 1 AND `close` = 0';
        }
        elseif( $mod === $this->orders )
        {
            $return = '`status` = 1';
        }

        return $return;
    }

    /**
     * @param $mod
     * Вывод даты последних изменений в модуле
     *
     * @return false|string
     */
    private function getLastmodByName($mod)
    {
        $query = superQuery(DB_PREFIX . $mod, '1=1 ORDER BY date DESC LIMIT 0,1');

        return $this->date($query);
    }
}