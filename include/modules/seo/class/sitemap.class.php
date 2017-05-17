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
    private $_sitemap_filename = 'sitemap.xml';
    /**
     * @var string
     */
    private $_posts = 'posts';
    /**
     * @var string
     */
    private $_catalog = 'catalog';
    /**
     * @var string
     */
    private $_orders = 'orders';
    /**
     * @var string
     */
    private $_pano = 'pano';
    /**
     * @var string
     */
    private $_ads = 'ads';

    /**
     * Sitemap constructor.
     */
    function __construct()
    {
        // Массив доступных модулей
        $this->sitemap_array = array(
            $this->_posts,
            $this->_catalog,
            $this->_orders,
            $this->_pano,
            $this->_ads,
        );
    }

    /**
     * Достаем список ключевых карт сайта (https://360.zp.ua/sitemap.xml)
     * @return array
     */
    public function getSitemapList()
    {
        $result_array = array();

        // Проходимся по массиву модулей
        foreach( $this->sitemap_array as $item )
        {
            // Собираем массив для вывода через шаблон. Ссылка, дата последнего изменения, приоритет
            $result_array[$item] = array(
                'loc' => '/' . $this->_sitemap_filename . '?type=' . $item,
                'lastmod' => $this->getLastmodByName($item),
                'priority' => 0.8,
            );
        }

        return $result_array;
    }

    /**
     * Подготовка списка для карты <url>list</url>
     *
     * @param string $mod Название модуля
     *
     * @return array
     */
    public function getList($mod)
    {
        $result_array = array();

        $and = $this->getAnd($mod); // меняем sql запрос в зависимости от модуля

        $list = mysqlRow(DB_PREFIX . $mod, $and . ' ORDER by id DESC');
        foreach( $list as $id => $item )
        {
            // Собираем массив для вывода через шаблон. Ссылка, дата последнего изменения, приоритет
            $result_array[$id] = array(
                'loc' => $this->getLoc($mod, $item), // меняем ссылку на элемент в зависимости от модуля
                'lastmod' => $this->getDate($item), // выставляем дату последнего изменения
                'priority' => 0.8,
            );
        }

        return $result_array;
    }

    /**
     * Выводим категории из всех модулей (https://360.zp.ua/sitemap.xml?type=other)
     * @return array
     */
    public function getOtherList()
    {
        // вывод списка категорий
        return array_merge($this->getCategory($this->_catalog . '_cat'), $this->getCategory($this->_posts . '_cat'), $this->getCategory($this->_pano . '_cat'), $this->getCategory($this->_ads . '_cat'));
    }

    /**
     * Подготовка отдельной категории
     *
     * @param string $mod Название модуля
     *
     * @return array
     */
    private function getCategory($mod)
    {
        $result_array = array();

        $sql = mysqlRow(DB_PREFIX . $mod, '1=1');

        foreach( $sql as $item )
        {
            // Собираем массив для вывода через шаблон. Ссылка, дата последнего изменения, приоритет
            $result_array[] = array(
                'loc' => $this->getLoc($mod, $item),
                'lastmod' => $this->getDate($item),
                'priority' => 0.6,
            );
        }

        return $result_array;
    }

    /**
     * Проверяем наличие даты изменения и выводим в формате Y-m-d
     *
     * @param array $item Массив должен содержать минимум один ключ "date"
     *
     * @return string Возвращает дату в формате Y-m-d
     */
    private function getDate($item)
    {
        if( !empty($item['edit_date']) && $item['edit_date'] !== '0000-00-00 00:00:00' )
            return date("Y-m-d", strtotime($item['edit_date']));
        elseif( $item['date'] !== '0000-00-00 00:00:00' )
            return date("Y-m-d", strtotime($item['date']));
        else
            return date("Y-m-d");
    }

    /**
     * Выводит ссылку на элемент
     *
     * @param string $mod Название модуля
     * @param array $item Массив элемента модуля (содержит ссылку, ID)
     *
     * @return string Возвращает web ссылку (начиная с /)
     */
    private function getLoc($mod, $item)
    {
        // Дефолт
        $result = '/';

        if( $mod === $this->_posts )
        {
            $result = '/' . $item['id'] . '-' . $item['alt_title'] . '.html';
        }
        elseif( $mod === $this->_catalog || $mod === $this->_ads )
        {
            $result = '/' . $mod . '/' . $item['alt_title'] . '.html';
        }
        elseif( $mod === $this->_orders )
        {
            $result = '/' . $this->_orders . '/' . $item['id'] . '/';
        }
        elseif( $mod === $this->_pano )
        {
            $result = '/' . $item['id'] . '-' . $this->_pano . '.html';
        }
        elseif( $mod === $this->_catalog . '_cat' )
        {
            $result = '/' . $this->_catalog . '/category/' . $item['alt_name'] . '/';
        }
        elseif( $mod === $this->_posts . '_cat' )
        {
            $result = '/' . $this->_posts . '/' . $item['alt_name'] . '/';
        }
        elseif( $mod === $this->_pano . '_cat' )
        {
            $result = '/' . $this->_pano . '/category/' . $item['alt_name'] . '/';
        }
        elseif( $mod === $this->_ads . '_cat' )
        {
            $result = '/' . $this->_ads . '/category/' . $item['alt_name'] . '/';
        }

        return htmlspecialchars($result);
    }

    /**
     * Меняем sql запрос в зависимости от модуля
     *
     * @param string $mod Название модуля
     *
     * @return string
     */
    private function getAnd($mod)
    {
        // Дефолт (большинство модулей)
        $result = 'publish = 1';

        if( $mod === $this->_ads )
        {
            $result = '`activate` = 1 AND `close` = 0';
        }
        elseif( $mod === $this->_orders )
        {
            $result = '`status` = 1';
        }

        return $result;
    }

    /**
     * Вывод даты последних изменений в модуле
     *
     * @param string $mod Название модуля
     *
     * @return string Возвращает дату в формате Y-m-d
     */
    private function getLastmodByName($mod)
    {
        $query = superQuery(DB_PREFIX . $mod, '1=1 ORDER BY date DESC LIMIT 0,1');

        return $this->getDate($query);
    }
}