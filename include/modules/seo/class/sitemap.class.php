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
     * @var string
     */
    private $list;
    /**
     * @var array
     */
    public $sitemap_array;
    private $sitemap_filename = 'sitemap.xml';
    private $posts = 'posts';
    private $catalog = 'catalog';
    private $orders = 'orders';
    private $pano = 'pano';
    private $ads = 'ads';

    /**
     * Sitemap constructor.
     */
    function __construct()
    {
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
     *
     * @return mixed
     */
    public function getSitemapList($home)
    {
        foreach( $this->sitemap_array as $item )
        {
            $this->list[$item] = array(
                'loc' => $home . '/' . $this->sitemap_filename . '?type=' . $item,
                'lastmod' => $this->getLastmodByMod($item),
            );
        }

        return $this->list;
    }

    public function getList($mod)
    {
        $return_array = array();

        $and = $this->get_and($mod);

        $list = mysqlRow(DB_PREFIX . $mod, $and . ' ORDER by id DESC');
        foreach( $list as $id => $item )
        {
            $return_array[$id] = array(
                'lastmod' => $this->date($item),
                'loc' => $this->get_loc($mod, $item),
            );
        }

        return $return_array;
    }

    public function getOtherList()
    {
        return array_merge($this->getCategory($this->catalog . '_cat'), $this->getCategory($this->posts . '_cat'), $this->getCategory($this->pano . '_cat'), $this->getCategory($this->ads . '_cat'));
    }

    private function getCategory($mod)
    {
        $return_array = array();

        $sql = mysqlRow(DB_PREFIX . $mod, '1=1');

        foreach( $sql as $item )
        {
            $return_array[] = array(
                'lastmod' => $this->date($item),
                'loc' => $this->get_loc($mod, $item),
                'priority' => 0.6,
            );
        }

        return $return_array;
    }

    private function date($item)
    {
        if( !empty($item['edit_date']) && $item['edit_date'] !== '0000-00-00 00:00:00' )
            return date("Y-m-d", strtotime($item['edit_date']));
        elseif( $item['date'] !== '0000-00-00 00:00:00' )
            return date("Y-m-d", strtotime($item['date']));
        else
            return date("Y-m-d");
    }

    private function get_loc($mod, $item)
    {
        global $set; // site preference

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

    private function get_and($mod)
    {
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
     *
     * @return false|string
     */
    private function getLastmodByMod($mod)
    {
        $query = superQuery(DB_PREFIX . $mod, '1=1 ORDER BY date DESC LIMIT 0,1');

        return $this->date($query);
    }
}