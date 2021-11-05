<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Admin;

use Pixel\TownHallBundle\Entity\Settings;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class SettingsAdmin extends Admin
{
    const TAB_VIEW = 'townhall.settings';
    const FORM_VIEW = 'townhall.settings.form';

    private ViewBuilderFactoryInterface $viewBuilderFactory;
    private SecurityCheckerInterface $securityChecker;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(Settings::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $categoryItem = new NavigationItem('townhall.settings');
            $categoryItem->setPosition(0);
            $categoryItem->setView(static::TAB_VIEW);

            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($categoryItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if ($this->securityChecker->hasPermission(Settings::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
            // sulu will only load the existing entity if the path of the form includes an id attribute
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::TAB_VIEW, '/townhall-settings/:id')
                    ->setResourceKey(Settings::RESOURCE_KEY)
                    ->setAttributeDefault('id', '-')
            );

            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(static::FORM_VIEW, '/details')
                    ->setResourceKey(Settings::RESOURCE_KEY)
                    ->setFormKey(Settings::FORM_KEY)
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions([new ToolbarAction('sulu_admin.save')])
                    ->setParent(static::TAB_VIEW)
            );
        }
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Settings' => [
                    Settings::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::EDIT,
                    ],
                ],
            ],
        ];
    }
}