<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Base;

use FacturaScripts\Dinamic\Lib\MenuItem;
use FacturaScripts\Dinamic\Model;

/**
 * Manage the use of the Facturascripts menu.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class MenuManager
{

    /**
     * Contains the structure of the menu for the user.
     *
     * @var MenuItem[]
     */
    private static $menu;

    /**
     * True when there is a menu active. Only for optimization purpose.
     *
     * @var bool
     */
    private static $menuActive;

    /**
     * Stores active page to use when reload.
     *
     * @var Model\Page
     */
    private static $menuPageActive;

    /**
     * Controller associated with the page
     *
     * @var Model\Page
     */
    private static $pageModel;

    /**
     * User for whom the menu has been created.
     *
     * @var Model\User|false
     */
    private static $user = false;

    /**
     * Returns the user's menu, the set of pages to which he has access.
     *
     * @return array
     */
    public function getMenu()
    {
        return self::$menu;
    }

    /**
     * Call only when you have connected to the database.
     */
    public function init()
    {
        if (self::$pageModel === null) {
            self::$pageModel = new Model\Page();
        }

        if (self::$user !== false && self::$menu === null) {
            self::$menu = $this->loadUserMenu();
        }
    }

    /**
     * Reloads menu from database.
     */
    public function reload()
    {
        self::$menu = $this->loadUserMenu();
        if (null !== self::$menuPageActive) {
            $this->setActiveMenu(self::$menuPageActive);
        }
    }

    /**
     * Removes all pages not present in $currentPaneNames.
     *
     * @param string[] $currentPageNames
     */
    public function removeOld($currentPageNames)
    {
        foreach (self::$pageModel->all([], [], 0, 0) as $page) {
            if (!in_array($page->name, $currentPageNames)) {
                $page->delete();
            }
        }
    }

    /**
     * Mark menu and menuitem as selected, and updates the data in the Model\Page
     * model based on the data in the getPageData() of the controller.
     *
     * @param array $pageData
     */
    public function selectPage($pageData)
    {
        $pageModel = self::$pageModel->get($pageData['name']);
        if ($pageModel === false) {
            $pageData['ordernum'] = 100;
            $pageModel = new Model\Page($pageData);
            $pageModel->save();
        } elseif ($this->pageNeedSave($pageModel, $pageData)) {
            $pageModel->menu = $pageData['menu'];
            $pageModel->submenu = $pageData['submenu'];
            $pageModel->showonmenu = $pageData['showonmenu'];
            $pageModel->title = $pageData['title'];
            $pageModel->icon = $pageData['icon'];
            $pageModel->ordernum = $pageData['ordernum'];
            $pageModel->save();
        }

        if (self::$menu !== null && self::$menuActive !== true) {
            $this->setActiveMenu($pageModel);
            self::$menuActive = true;
        }
    }

    /**
     * Assign the user to load their menu.
     *
     * @param Model\User|false $user
     */
    public function setUser($user)
    {
        self::$user = $user;
        $this->init();
    }

    /**
     * Returns all access data from the user.
     *
     * @param string $nick
     *
     * @return Model\RoleAccess[]
     */
    private function getUserAccess($nick)
    {
        $access = [];
        $roleUserModel = new Model\RoleUser();
        $filter = [new DataBase\DataBaseWhere('nick', $nick)];
        foreach ($roleUserModel->all($filter) as $roleUser) {
            foreach ($roleUser->getRoleAccess() as $roleAccess) {
                $access[] = $roleAccess;
            }
        }

        return $access;
    }

    /**
     * Load the list of pages for the user.
     *
     * @return Model\Page[]
     */
    private function loadPages()
    {
        $where = [new DataBase\DataBaseWhere('showonmenu', true)];
        $order = [
            'lower(menu)' => 'ASC',
            'lower(submenu)' => 'ASC',
            'ordernum' => 'ASC',
            'lower(title)' => 'ASC',
        ];

        $pages = self::$pageModel->all($where, $order, 0, 0);
        if (self::$user && self::$user->admin) {
            return $pages;
        }

        $result = [];
        $userAccess = $this->getUserAccess(self::$user->nick);
        foreach ($pages as $page) {
            foreach ($userAccess as $pageRule) {
                if ($page->name === $pageRule->pagename) {
                    $result[] = $page;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Load the menu structure for the user.
     *
     * @return array
     */
    private function loadUserMenu()
    {
        $result = [];
        $menuValue = '';
        $submenuValue = null;
        $menuItem = null;
        $i18n = new Translator();

        /// We load the list of pages for the user
        $pages = $this->loadPages();
        foreach ($pages as $page) {
            if ($page->menu === '') {
                continue;
            }

            /// Menu break control
            if ($menuValue !== $page->menu) {
                $menuValue = $page->menu;
                $submenuValue = null;
                $result[$menuValue] = new MenuItem($menuValue, $i18n->trans($menuValue), '#');
                $menuItem = &$result[$menuValue]->menu;
            }

            /// Submenu break control
            if ($submenuValue !== $page->submenu) {
                $submenuValue = $page->submenu;
                $menuItem = &$result[$menuValue]->menu;
                if (!empty($submenuValue)) {
                    $menuItem[$submenuValue] = new MenuItem($submenuValue, $i18n->trans($submenuValue), '#');
                    $menuItem = &$menuItem[$submenuValue]->menu;
                }
            }
            $menuItem[$page->name] = new MenuItem($page->name, $i18n->trans($page->title), $page->url(), $page->icon);
        }

        return $this->sortMenu($result);
    }

    /**
     * Returns if the page should be saved.
     *
     * @param Model\Page $pageModel
     * @param array      $pageData
     *
     * @return bool
     */
    private function pageNeedSave($pageModel, $pageData)
    {
        return
            ($pageModel->menu !== $pageData['menu']) || ($pageModel->submenu !== $pageData['submenu']) ||
            ($pageModel->title !== $pageData['title']) || ($pageModel->icon !== $pageData['icon']) ||
            ($pageModel->showonmenu !== $pageData['showonmenu'])
        ;
    }

    /**
     * Set the active menu.
     *
     * @param Model\Page $pageModel
     */
    private function setActiveMenu($pageModel)
    {
        foreach (self::$menu as $key => $menuItem) {
            if ($menuItem->name === $pageModel->menu) {
                self::$menu[$key]->active = true;
                $this->setActiveMenuItem(self::$menu[$key]->menu, $pageModel);
                break;
            }
        }
    }

    /**
     * Assign active menu item.
     *
     * @param MenuItem[] $menu
     * @param Model\Page $pageModel
     */
    private function setActiveMenuItem(&$menu, $pageModel)
    {
        foreach ($menu as $key => $menuItem) {
            if ($menuItem->name === $pageModel->name) {
                $menu[$key]->active = true;
                self::$menuPageActive = $pageModel;
                break;
            } elseif (!empty($pageModel->submenu) && !empty($menuItem->menu) && $menuItem->name === $pageModel->submenu) {
                $menu[$key]->active = true;
                $this->setActiveMenuItem($menu[$key]->menu, $pageModel);
                break;
            }
        }
    }

    /**
     * Sorts menu and submenus by title.
     *
     * @param array $result
     *
     * @return array
     */
    private function sortMenu(&$result)
    {
        /// sort this menu
        uasort($result, function ($menu1, $menu2) {
            return strcasecmp($menu1->title, $menu2->title);
        });

        /// sort submenus
        foreach ($result as $key => $value) {
            if (!empty($value->menu)) {
                $result[$key]->menu = $this->sortMenu($value->menu);
            }
        }

        return $result;
    }
}
