import type { AppModeState } from '@/types/app-mode';
import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            appMode: AppModeState | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
