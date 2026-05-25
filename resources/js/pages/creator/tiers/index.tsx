import { Form, Head, Link, router } from '@inertiajs/react';
import { Layers, Pencil, Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create as createTier, index as creatorTiers, edit as editTier, submit as submitTier, activate as activateTier, archive as archiveTier } from '@/routes/creator/tiers';
import type { CreatorTierListItem } from '@/types/tier';

type CreatorTiersIndexProps = {
    tiers: CreatorTierListItem[];
};

function statusVariant(
    status: CreatorTierListItem['status'],
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'active':
        case 'approved':
            return 'default';
        case 'rejected':
            return 'destructive';
        case 'requested':
            return 'secondary';
        default:
            return 'outline';
    }
}

export default function CreatorTiersIndex({ tiers }: CreatorTiersIndexProps) {
    return (
        <>
            <Head title="Creator tiers" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4">
                        <div>
                            <CardTitle>Support tiers</CardTitle>
                            <CardDescription>
                                Request paid tiers for admin approval before they
                                appear on your public profile.
                            </CardDescription>
                        </div>
                        <Button asChild>
                            <Link href={createTier()} prefetch>
                                <Plus className="size-4" />
                                New tier
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {tiers.length === 0 ? (
                            <div className="flex flex-col items-center gap-3 py-10 text-center">
                                <Layers className="text-muted-foreground size-10" />
                                <p className="text-muted-foreground text-sm">
                                    No tiers yet. Create a draft and submit it
                                    for review.
                                </p>
                                <Button asChild variant="outline">
                                    <Link href={createTier()} prefetch>
                                        Create tier
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            tiers.map((tier) => (
                                <div
                                    key={tier.id}
                                    className="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="space-y-2">
                                        <p className="font-medium">{tier.name}</p>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant={statusVariant(tier.status)}>
                                                {tier.status}
                                            </Badge>
                                            <span className="text-muted-foreground text-sm">
                                                {tier.monthly_price} / month
                                                {tier.annual_price
                                                    ? ` · ${tier.annual_price} / year`
                                                    : null}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Button asChild size="sm" variant="outline">
                                            <Link href={editTier(tier.id)} prefetch>
                                                <Pencil className="size-4" />
                                                Manage
                                            </Link>
                                        </Button>
                                        {tier.can_submit ? (
                                            <Button
                                                size="sm"
                                                onClick={() =>
                                                    router.post(submitTier(tier.id).url)
                                                }
                                            >
                                                Submit for review
                                            </Button>
                                        ) : null}
                                        {tier.can_activate ? (
                                            <Button
                                                size="sm"
                                                onClick={() =>
                                                    router.post(activateTier(tier.id).url)
                                                }
                                            >
                                                Activate
                                            </Button>
                                        ) : null}
                                        {tier.can_archive ? (
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                onClick={() =>
                                                    router.post(archiveTier(tier.id).url)
                                                }
                                            >
                                                Archive
                                            </Button>
                                        ) : null}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreatorTiersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Creator tiers',
            href: creatorTiers(),
        },
    ],
};
