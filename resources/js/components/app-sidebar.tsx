import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarRail } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { LayoutGrid, File, FileText, Settings, Users, Shield, Image, Paintbrush, AlignJustify, Puzzle } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: route('admin.dashboard'),
        icon: LayoutGrid,
    },
];

const contentNavItems = [
    {
        title: 'Pages',
        href: route('admin.pages.index'),
        icon: File,
    },
    {
        title: 'Posts',
        icon: FileText,
        items: [
            {
                title: 'All Posts',
                href: route('admin.posts.index'),
            },
            {
                title: 'Categories',
                href: route('admin.categories.index'),
            },
            {
                title: 'Tags',
                href: route('admin.tags.index'),
            },
        ],
    },
    {
        title: 'Media',
        href: route('admin.media.index'),
        icon: Image,
    },
    {
        title: 'Menus',
        href: route('admin.menus.index'),
        icon: AlignJustify,
    },

] as NavItem[];

const adminNavItems: NavItem[] = [
    {
        title: 'Users',
        href: route('admin.users.index'),
        icon: Users,
    },
    {
        title: 'Roles',
        href: route('admin.roles.index'),
        icon: Shield,
    },
    {
        title: 'Themes',
        href: route('admin.themes.index'),
        icon: Paintbrush,
    },
    {
        title: 'Plugins',
        href: route('admin.plugins.index'),
        icon: Puzzle,
    },
    {
        title: 'Settings',
        href: route('admin.settings.general'),
        icon: Settings,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('admin.dashboard')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavMain items={contentNavItems} label="Content Management" />
                <NavMain items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
