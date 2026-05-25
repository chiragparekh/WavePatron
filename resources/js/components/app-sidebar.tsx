import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Music2, Upload } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as audios } from '@/routes/audios';
import { create as uploadsCreate } from '@/routes/uploads';
import type { AppModeState, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Audios',
        href: audios(),
        icon: Music2,
    },
    {
        title: 'Upload',
        href: uploadsCreate(),
        icon: Upload,
    },
];

export function AppSidebar() {
    const appMode = usePage<{ appMode: AppModeState | null }>().props.appMode;
    const navItems = mainNavItems.filter(
        (item) => item.title !== 'Upload' || appMode?.active === 'creator',
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
