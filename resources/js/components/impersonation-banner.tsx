import { usePage } from '@inertiajs/react';
import type { ImpersonationState } from '@/types';

export function ImpersonationBanner() {
    const impersonation = usePage().props.impersonation as
        | ImpersonationState
        | null
        | undefined;

    if (! impersonation?.active) {
        return null;
    }

    return (
        <div
            id="impersonate-banner"
            className="fixed inset-x-0 top-0 z-50 flex h-[50px] items-center justify-center gap-5 border-b border-neutral-700 bg-neutral-800 px-4 text-sm text-neutral-100 dark:border-neutral-600 dark:bg-neutral-900"
        >
            <p>
                Impersonating{' '}
                <strong className="font-semibold">
                    {impersonation.user.name}
                </strong>
            </p>
            <a
                href={impersonation.leaveUrl}
                className="rounded-md bg-neutral-100 px-5 py-1 text-neutral-900 transition hover:bg-white dark:bg-neutral-200 dark:hover:bg-neutral-100"
            >
                Leave impersonation
            </a>
        </div>
    );
}
