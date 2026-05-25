import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TierController from '@/actions/App/Http/Controllers/Creator/TierController';
import { edit as editTier, index as creatorTiers, submit as submitTier, activate as activateTier, archive as archiveTier } from '@/routes/creator/tiers';
import type { CreatorTierDetail } from '@/types/tier';

type CreatorTierEditProps = {
    tier: CreatorTierDetail;
};

export default function CreatorTierEdit({ tier }: CreatorTierEditProps) {
    const [benefits, setBenefits] = useState(
        tier.benefits.length > 0 ? tier.benefits : [''],
    );

    return (
        <>
            <Head title={`Manage ${tier.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{tier.name}</CardTitle>
                        <CardDescription>
                            Manage your tier draft or review its approval status.
                        </CardDescription>
                        <div className="flex flex-wrap gap-2 pt-2">
                            <Badge variant="outline">{tier.status}</Badge>
                            {tier.is_subscribable ? (
                                <Badge>Subscribable</Badge>
                            ) : (
                                <Badge variant="secondary">
                                    Not subscribable yet
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {tier.is_editable ? (
                            <Form
                                {...TierController.update.form(tier.id)}
                                options={{ preserveScroll: true }}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Name</Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                defaultValue={tier.name}
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label>Benefits</Label>
                                            {benefits.map((benefit, index) => (
                                                <Input
                                                    key={index}
                                                    name={`benefits[${index}]`}
                                                    defaultValue={benefit}
                                                />
                                            ))}
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setBenefits([
                                                        ...benefits,
                                                        '',
                                                    ])
                                                }
                                            >
                                                Add benefit
                                            </Button>
                                            <InputError
                                                message={errors.benefits}
                                            />
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="monthly_price">
                                                    Monthly price (USD)
                                                </Label>
                                                <Input
                                                    id="monthly_price"
                                                    name="monthly_price"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    defaultValue={
                                                        tier.monthly_price
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        errors.monthly_price
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="annual_price">
                                                    Annual price (USD, optional)
                                                </Label>
                                                <Input
                                                    id="annual_price"
                                                    name="annual_price"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    defaultValue={
                                                        tier.annual_price ?? ''
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        errors.annual_price
                                                    }
                                                />
                                            </div>
                                        </div>

                                        <Button
                                            disabled={processing}
                                            type="submit"
                                        >
                                            Save changes
                                        </Button>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <div className="space-y-3 text-sm">
                                <p>
                                    <span className="font-medium">
                                        Monthly:
                                    </span>{' '}
                                    ${tier.monthly_price}
                                </p>
                                {tier.annual_price ? (
                                    <p>
                                        <span className="font-medium">
                                            Annual:
                                        </span>{' '}
                                        ${tier.annual_price}
                                    </p>
                                ) : null}
                                <ul className="list-disc space-y-1 pl-5">
                                    {tier.benefits.map((benefit) => (
                                        <li key={benefit}>{benefit}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <div className="flex flex-wrap gap-2 border-t pt-4">
                            {tier.can_submit ? (
                                <Button
                                    onClick={() => router.post(submitTier(tier.id).url)}
                                >
                                    Submit for review
                                </Button>
                            ) : null}
                            {tier.can_activate ? (
                                <Button
                                    onClick={() => router.post(activateTier(tier.id).url)}
                                >
                                    Activate tier
                                </Button>
                            ) : null}
                            {tier.can_archive ? (
                                <Button
                                    variant="secondary"
                                    onClick={() => router.post(archiveTier(tier.id).url)}
                                >
                                    Archive tier
                                </Button>
                            ) : null}
                            <Button asChild variant="outline">
                                <Link href={creatorTiers()} prefetch>
                                    Back to tiers
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreatorTierEdit.layout = {
    breadcrumbs: [
        {
            title: 'Creator tiers',
            href: creatorTiers(),
        },
        {
            title: 'Manage tier',
            href: editTier(0),
        },
    ],
};
