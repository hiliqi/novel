<?php


namespace app\admin\controller;

use app\model\ArticleArticle;
use app\model\SystemUsers;
use app\service\AuthorService;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\facade\View;

class Authors extends Base
{

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function index()
    {
        return view();
    }

    public function list()
    {
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $data = SystemUsers::where('groupid', '=', 6);
        $count = $data->count();
        $authors = $data->order('uid', 'desc')
            ->limit(($page - 1) * $limit, $limit)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $authors
        ]);
    }

    public function edit()
    {
        $data = request()->param();
        try {
            $author = SystemUsers::where('uid', '=', $data['uid'])->findOrFail();
            if (request()->isPost()) {
                $author->name = $data['name'];
                if (empty($data['password']) || is_null($data['password'])) {

                } else {
                    $author->pass = md5(trim($data['password']) . 'abc');
                }
                $result = $author->save();
                if ($result) {
                    return json(['err' => 0, 'msg' => '修改成功']);
                } else {
                    return json(['err' => 1, 'msg' => '修改失败']);
                }
            }
            View::assign([
                'author' => $author,
            ]);
        } catch (ModelNotFoundException $e) {
            return json(['err' => 1, 'msg' => $e->getMessage()]);
        }
        return view();
    }

    public function delete()
    {
        $id = input('uid');
        try {
            $author = SystemUsers::findOrFail($id);
            $books = ArticleArticle::where('authorid', '=', $id)->select();
            if ($books->count() > 0) {
                return json(['err' => '1', 'msg' => '该作者名下还有作品，请先删除所有作品']);
            }
            $result = $author->delete();
            if ($result) {
                return json(['err' => '0', 'msg' => '删除成功']);
            } else {
                return json(['err' => '1', 'msg' => '删除失败']);
            }
        } catch (ModelNotFoundException $e) {
            return json(['err' => '0', 'msg' => $e->getMessage()]);
        }
    }

    public function search()
    {
        $name = input('name');
        $uname = input('uname');
        $where[] = ['groupid', '=', 6];
        if (isset($name)) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if (isset($uname)) {
            $where[] = ['uname', 'like', '%' . $uname . '%'];
        }
        $page = intval(input('page'));
        $limit = intval(input('limit'));
        $data = SystemUsers::where($where);
        $count = $data->count();
        $authors = $data->order('uid', 'desc')
            ->limit(($page - 1) * $limit, $limit)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $authors
        ]);
    }
}