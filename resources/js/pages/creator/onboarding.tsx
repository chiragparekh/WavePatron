import { Head } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { onboarding } from '@/routes/creator';

export default function CreatorOnboarding() {
    return (
        <>
            <Head title="Creator onboarding" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Set up your creator profile</CardTitle>
                        <CardDescription>
                            Complete your public creator profile before
                            publishing audio or tiers.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-muted-foreground text-sm">
                            Creator profile setup arrives in Phase 2. This page
                            is the onboarding destination when you switch to
                            creator mode without a profile yet.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreatorOnboarding.layout = {
    breadcrumbs: [
        {
            title: 'Creator onboarding',
            href: onboarding(),
        },
    ],
};
