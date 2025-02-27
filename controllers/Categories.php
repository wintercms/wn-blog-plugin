<?php

namespace Winter\Blog\Controllers;

use Backend\Classes\Controller;
use Illuminate\Support\Facades\Lang;
use Winter\Blog\Models\Category;
use Winter\Storm\Support\Facades\Flash;

class Categories extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\ReorderController::class,
    ];

    public $requiredPermissions = ['winter.blog.access_categories'];

    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $categoryId) {
                if ((!$category = Category::find($categoryId))) {
                    continue;
                }

                $category->delete();
            }

            Flash::success(Lang::get('winter.blog::lang.category.delete_success'));
        }

        return $this->listRefresh();
    }
}
