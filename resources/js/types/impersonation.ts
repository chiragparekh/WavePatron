export type ImpersonationState = {
    active: true;
    user: {
        name: string;
        email: string;
    };
    leaveUrl: string;
};
