<?php

namespace ZOOlanders\YOOtheme\J2Commerce\Module\Source\Listener;

use Joomla\CMS\Document\Document;

class MatchTemplate
{
    public Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle($view, $tpl): ?array
    {
        // dd($tpl);
        if ($tpl !== 'source') {
            return null;
        }

        $name = $view->getName();
        $layout = $view->getLayout();

        // $context = $view->get('context');
        if ($name === 'carts' && $layout === 'source') {
            $items = $view->get('items');

            // dd($items);

            return [
                'type' => 'com_j2store.carts',
                'query' => [
                    // 'catid' => $item->category->id,
                    // 'tag' => array_column($item->tags ?? [], 'id'),
                    'lang' => $this->document->language,
                ],
                'params' => [
                    'items' => $items,
                ],
                // 'editUrl' => $item->canEdit()
                //     ? $item->app->route->submission($view->application->getItemEditSubmission(), $item->type, null, $item->id, 'itemedit')
                //     : null,
            ];
        }

        if (static::isView($view, 'category', 'itemlist')) {
            $pagination = $view->get('pagination');
            $categories = array_filter(array_map(function ($cat) {
                return K2Helper::getCategory($cat);
            }, $view->get('params')->get('categories', [])));

            return [
                'type' => 'com_j2store.category',
                'query' => [
                    'catid' => array_map(function($cat) {
                        return $cat->id;
                    }, $categories),
                    'pages' => $pagination->pagesCurrent === 1 ? 'first' : 'except_first',
                    'lang' => $this->document->language,
                ],
                'params' => [
                    'categories' => $categories,
                    'items' => array_merge($view->get('leading'), $view->get('primary'), $view->get('secondary'), $view->get('links')),
                    'pagination' => $pagination,
                ]
            ];
        }

        if (static::isView($view, 'latest') && $view->get('source') === 'categories') {
            $categories = $view->get('blocks');

            return [
                'type' => 'com_j2store.category.latest',
                'query' => [
                    'catid' => array_map(function($cat) {
                        return $cat->id;
                    }, $categories),
                    'lang' => $this->document->language,
                ],
                'params' => [
                    'categories' => $categories,
                    'items' => array_reduce($categories, function($res, $cat) {
                        return array_merge($res, $cat->items);
                    }, [])
                ]
            ];
        }

        if (static::isView($view, 'tag', 'itemlist')) {
            $pagination = $view->get('pagination');

            return [
                'type' => 'com_j2store.tag',
                'query' => [
                    'pages' => $pagination->pagesCurrent === 1 ? 'first' : 'except_first',
                    'lang' => $this->document->language,
                ],
                'params' => [
                    'tag' => $view,
                    'items' => $view->get('items'),
                    'pagination' => $pagination,
                ],
            ];
        }

        if (static::isView($view, 'latest') && $view->get('source') === 'users') {
            $users = $view->get('blocks');

            return [
                'type' => 'com_j2store.item.latest',
                'query' => [
                    'lang' => $this->document->language,
                ],
                'params' => [
                    'users' => $users,
                    'items' => array_reduce($users, function($res, $user) {
                        return array_merge($res, $user->items);
                    }, [])
                ]
            ];
        }

        return null;
    }

    protected static function isView($view, $task, $name = null)
    {
        return $view->getName() === ($name ?? $task) && $view->getLayout() === $task;
    }
}