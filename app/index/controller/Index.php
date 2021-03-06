<?php


namespace app\index\controller;


use app\common\RedisHelper;
use app\model\Banner;
use app\model\Cate;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\facade\View;

class Index extends Base
{
    protected $bookService;

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->bookService = app('bookService');
    }

    public function index()
    {
        $banners = cache('banners');
        if (!$banners) {
            $banners = Banner::with('book')->order('banner_order', 'desc')->select();
            foreach ($banners as &$banner) {
                if (substr($banner['pic'], 0, 4) === 'http') {

                } else {
                    $banner['pic'] = $this->server . $banner['pic'];
                }
            }
            cache('banners', $banners, null, 'redis');
        }

        $hot_books = $this->bookService->getHotBooks($this->prefix, $this->end_point);
        $newest = $this->bookService->getBooks($this->end_point, 'lastupdate', '1=1', 30);
        $newbie = $this->bookService->getBooks($this->end_point, 'postdate', '1=1', 30);
        $ends = $this->bookService->getBooks($this->end_point, 'lastupdate', [['fullflag', '=', '1']], 30);

        $cates = cache('cates');
        if (!$cates) {
            $cates = Cate::select();
            cache('cates', $cates, null, 'redis');
        }

        $catelist = array(); //分类漫画数组
        $cateItem = array();
        foreach ($cates as $cate) {
            $books = $this->bookService->getByCate($cate->sortid, $this->end_point);
            $cateItem['books'] = $books->toArray();
            $cateItem['cate'] = ['id' => $cate->sortid, 'cate_name' => $cate->cate_name];
            $catelist[] = $cateItem;
        }

        View::assign([
            'banners' => $banners,
            'banners_count' => count($banners),
            'newest' => $newest,
            'hot' => $hot_books,
            'ends' => $ends,
            'newbie' => $newbie,
            'cates' => $cates,
            'catelist' => $catelist
        ]);
        return view($this->tpl);
    }

    public function search()
    {
        $keyword = input('keyword');
        $redis = RedisHelper::GetInstance();
        $redis->zIncrBy($this->redis_prefix . 'hot_search', 1, $keyword); //搜索词写入redis热搜
        $hot_search_json = $redis->zRevRange($this->redis_prefix . 'hot_search', 0, 4, true);
        $hot_search = array();
        foreach ($hot_search_json as $k => $v) {
            $hot_search[] = $k;
        }

        $books = $this->bookService->search($keyword, $this->end_point, $this->prefix);

        View::assign([
            'books' => $books,
            'count' => count($books),
            'hot_search' => $hot_search,
            'keyword' => $keyword
        ]);
        return view($this->tpl);
    }
}