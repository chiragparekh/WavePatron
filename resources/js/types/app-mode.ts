export type AppMode = 'listener' | 'creator';

export type AppModeState = {
    active: AppMode;
    available: AppMode[];
    canSwitch: boolean;
};
