import { Form, Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { onboarding } from '@/routes/creator/payouts';
import { dashboard as creatorDashboard } from '@/routes/creator';

type PayoutStatus =
    | 'not_started'
    | 'pending'
    | 'enabled'
    | 'restricted';

export default function CreatorPayouts({
    payout,
}: {
    payout: {
        status: PayoutStatus;
        stripe_connect_account_id: string | null;
        can_onboard: boolean;
    };
}) {
    const statusLabel: Record<PayoutStatus, string> = {
        not_started: 'Not started',
        pending: 'Pending review',
        enabled: 'Enabled',
        restricted: 'Restricted',
    };

    return (
        <>
            <Head title="Creator payouts" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Payout setup</CardTitle>
                        <CardDescription>
                            Connect your Stripe Express account so paid tier
                            subscriptions can pay out to you.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-medium">Status</span>
                            <Badge variant="secondary">
                                {statusLabel[payout.status]}
                            </Badge>
                        </div>

                        {payout.stripe_connect_account_id ? (
                            <p className="text-muted-foreground text-sm">
                                Connected account:{' '}
                                {payout.stripe_connect_account_id}
                            </p>
                        ) : null}

                        {payout.can_onboard ? (
                            <Form {...onboarding.form()} className="inline">
                                <Button type="submit">
                                    Continue Stripe onboarding
                                </Button>
                            </Form>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Payouts are enabled. Paid tiers can accept new
                                subscribers.
                            </p>
                        )}

                        <Link
                            href={creatorDashboard()}
                            className="text-primary block text-sm underline"
                        >
                            Back to creator dashboard
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreatorPayouts.layout = {
    breadcrumbs: [
        {
            title: 'Creator dashboard',
            href: creatorDashboard(),
        },
        {
            title: 'Payouts',
            href: onboarding.url(),
        },
    ],
};
