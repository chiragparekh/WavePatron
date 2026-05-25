import { router, usePage } from '@inertiajs/react';
import { Mic2, Radio } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { update as updateAccountMode } from '@/routes/account/mode';
import type { AppMode } from '@/types/app-mode';

const modeLabels: Record<AppMode, string> = {
    listener: 'Listener',
    creator: 'Creator',
};

const modeIcons: Record<AppMode, typeof Radio> = {
    listener: Radio,
    creator: Mic2,
};

const oppositeMode: Record<AppMode, AppMode> = {
    listener: 'creator',
    creator: 'listener',
};

export function AppModeSwitcher() {
    const { appMode } = usePage().props;

    if (!appMode?.canSwitch) {
        return null;
    }

    const targetMode = oppositeMode[appMode.active];
    const TargetIcon = modeIcons[targetMode];

    return (
        <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => {
                router.put(updateAccountMode.url(), { mode: targetMode });
            }}
            aria-label={`Switch to ${modeLabels[targetMode]}`}
            data-test="app-mode-switcher"
        >
            <TargetIcon className="size-3.5" />
            <span className="hidden sm:inline">
                Switch to {modeLabels[targetMode]}
            </span>
        </Button>
    );
}
