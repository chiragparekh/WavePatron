import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { ImpersonationBanner } from '@/components/impersonation-banner';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant, ImpersonationState } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const page = usePage();
    const isOpen = page.props.sidebarOpen;
    const isImpersonating = Boolean(
        (page.props.impersonation as ImpersonationState | null | undefined)
            ?.active,
    );
    const shellClassName = isImpersonating ? 'pt-[50px]' : undefined;

    if (variant === 'header') {
        return (
            <div className={`flex min-h-screen w-full flex-col ${shellClassName ?? ''}`}>
                <ImpersonationBanner />
                {children}
            </div>
        );
    }

    return (
        <div className={shellClassName}>
            <ImpersonationBanner />
            <SidebarProvider defaultOpen={isOpen}>{children}</SidebarProvider>
        </div>
    );
}
