import { router, usePage } from '@inertiajs/react';
import { Mic2, Radio } from 'lucide-react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
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

export function AppModeSwitcher() {
    const { appMode } = usePage().props;

    if (!appMode?.canSwitch) {
        return null;
    }

    return (
        <ToggleGroup
            type="single"
            variant="outline"
            size="sm"
            value={appMode.active}
            onValueChange={(value) => {
                if (!value || value === appMode.active) {
                    return;
                }

                router.put(updateAccountMode.url(), { mode: value });
            }}
            aria-label="Account mode"
            data-test="app-mode-switcher"
        >
            {appMode.available.map((mode) => {
                const Icon = modeIcons[mode];

                return (
                    <ToggleGroupItem
                        key={mode}
                        value={mode}
                        aria-label={modeLabels[mode]}
                        className="gap-1.5 px-2.5"
                    >
                        <Icon className="size-3.5" />
                        <span className="hidden sm:inline">
                            {modeLabels[mode]}
                        </span>
                    </ToggleGroupItem>
                );
            })}
        </ToggleGroup>
    );
}
