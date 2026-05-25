import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import { create as createTier, index as creatorTiers } from '@/routes/creator/tiers';

export default function CreatorTierCreate() {
    const [benefits, setBenefits] = useState(['']);

    return (
        <>
            <Head title="Create tier" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Form
                    {...TierController.store.form()}
                    options={{ preserveScroll: true }}
                    className="max-w-2xl"
                >
                    {({ processing, errors }) => (
                        <Card>
                            <CardHeader>
                                <CardTitle>New tier</CardTitle>
                                <CardDescription>
                                    Draft a tier and submit it for admin approval.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Supporter"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Benefits</Label>
                                    {benefits.map((benefit, index) => (
                                        <div key={index} className="flex gap-2">
                                            <Input
                                                name={`benefits[${index}]`}
                                                defaultValue={benefit}
                                                placeholder="Early access to new releases"
                                            />
                                        </div>
                                    ))}
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setBenefits([...benefits, ''])
                                        }
                                    >
                                        Add benefit
                                    </Button>
                                    <InputError message={errors.benefits} />
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
                                            placeholder="5.00"
                                        />
                                        <InputError
                                            message={errors.monthly_price}
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
                                            placeholder="50.00"
                                        />
                                        <InputError
                                            message={errors.annual_price}
                                        />
                                    </div>
                                </div>

                                <div className="flex gap-2">
                                    <Button disabled={processing} type="submit">
                                        Save draft
                                    </Button>
                                    <Button asChild variant="outline">
                                        <Link href={creatorTiers()} prefetch>
                                            Cancel
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </Form>
            </div>
        </>
    );
}

CreatorTierCreate.layout = {
    breadcrumbs: [
        {
            title: 'Creator tiers',
            href: creatorTiers(),
        },
        {
            title: 'Create tier',
            href: createTier(),
        },
    ],
};
