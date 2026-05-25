import { Head, Link, router } from '@inertiajs/react';
import { Lock, Music2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show } from '@/routes/creators';
import type {
    CreatorAudioPlaceholder,
    CreatorTierPlaceholder,
    PublicCreatorProfile,
} from '@/types/creator-profile';

export default function CreatorPublicProfile({
    profile,
    freeAudio,
    premiumAudio,
    tiers,
}: {
    profile: PublicCreatorProfile;
    freeAudio: CreatorAudioPlaceholder[];
    premiumAudio: CreatorAudioPlaceholder[];
    tiers: CreatorTierPlaceholder[];
}) {
    return (
        <>
            <Head title={profile.display_name} />
            <div className="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="border-b border-[#19140035] dark:border-[#3E3E3A]">
                    {profile.cover_image_url ? (
                        <img
                            src={profile.cover_image_url}
                            alt=""
                            className="h-48 w-full object-cover md:h-64"
                        />
                    ) : (
                        <div className="h-48 w-full bg-neutral-200 dark:bg-neutral-800 md:h-64" />
                    )}
                    <div className="mx-auto flex max-w-4xl items-end gap-4 px-6 pb-6">
                        {profile.avatar_url ? (
                            <img
                                src={profile.avatar_url}
                                alt=""
                                className="-mt-12 size-24 rounded-full border-4 border-[#FDFDFC] object-cover dark:border-[#0a0a0a]"
                            />
                        ) : (
                            <div className="-mt-12 size-24 rounded-full border-4 border-[#FDFDFC] bg-neutral-300 dark:border-[#0a0a0a] dark:bg-neutral-700" />
                        )}
                        <div className="flex-1 pt-4">
                            <h1 className="text-2xl font-semibold">
                                {profile.display_name}
                            </h1>
                            <p className="text-muted-foreground text-sm">
                                @{profile.handle}
                            </p>
                            {profile.tagline ? (
                                <p className="mt-2 text-sm">{profile.tagline}</p>
                            ) : null}
                        </div>
                    </div>
                </header>

                <main className="mx-auto grid max-w-4xl gap-8 px-6 py-8">
                    {profile.bio ? (
                        <section>
                            <h2 className="mb-2 text-lg font-medium">About</h2>
                            <p className="text-muted-foreground text-sm whitespace-pre-wrap">
                                {profile.bio}
                            </p>
                        </section>
                    ) : null}

                    {profile.categories.length > 0 ? (
                        <section className="flex flex-wrap gap-2">
                            {profile.categories.map((category) => (
                                <Badge key={category} variant="secondary">
                                    {category}
                                </Badge>
                            ))}
                        </section>
                    ) : null}

                    <section className="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Music2 className="size-4" />
                                    Free audio
                                </CardTitle>
                                <CardDescription>
                                    Published free tracks appear here in a
                                    later phase.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {freeAudio.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        No free audio published yet.
                                    </p>
                                ) : (
                                    <ul className="space-y-2 text-sm">
                                        {freeAudio.map((item) => (
                                            <li key={item.id}>{item.title}</li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Lock className="size-4" />
                                    Premium audio
                                </CardTitle>
                                <CardDescription>
                                    Subscriber-only tracks show as locked until
                                    you subscribe.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {premiumAudio.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        No premium audio published yet.
                                    </p>
                                ) : (
                                    <ul className="space-y-2 text-sm">
                                        {premiumAudio.map((item) => (
                                            <li
                                                key={item.id}
                                                className="flex items-center gap-2"
                                            >
                                                <Lock className="size-3.5 shrink-0" />
                                                {item.title}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </section>

                    <section>
                        <h2 className="mb-4 text-lg font-medium">
                            Support tiers
                        </h2>
                        {tiers.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Approved tiers will appear here once available.
                            </p>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2">
                                {tiers.map((tier) => (
                                    <Card key={tier.id}>
                                        <CardHeader>
                                            <CardTitle className="text-base">
                                                {tier.name}
                                            </CardTitle>
                                            <CardDescription>
                                                {tier.monthly_price} / month
                                                {tier.annual_price
                                                    ? ` · ${tier.annual_price} / year`
                                                    : null}
                                            </CardDescription>
                                        </CardHeader>
                                        {tier.benefits.length > 0 ? (
                                            <CardContent>
                                                <ul className="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                                                    {tier.benefits.map(
                                                        (benefit) => (
                                                            <li key={benefit}>
                                                                {benefit}
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                                {!tier.is_subscribable ? (
                                                    <p className="text-muted-foreground mt-3 text-xs">
                                                        Subscriptions open once
                                                        this creator completes
                                                        payout setup.
                                                    </p>
                                                ) : tier.subscribe_url ? (
                                                    <Button
                                                        className="mt-3"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.visit(
                                                                tier.subscribe_url!,
                                                            )
                                                        }
                                                    >
                                                        Subscribe
                                                    </Button>
                                                ) : null}
                                            </CardContent>
                                        ) : null}
                                    </Card>
                                ))}
                            </div>
                        )}
                    </section>

                    {(profile.website || profile.support_email) && (
                        <section className="text-sm">
                            {profile.website ? (
                                <p>
                                    <a
                                        href={profile.website}
                                        className="underline"
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Website
                                    </a>
                                </p>
                            ) : null}
                            {profile.support_email ? (
                                <p className="text-muted-foreground mt-1">
                                    Contact: {profile.support_email}
                                </p>
                            ) : null}
                        </section>
                    )}
                </main>

                <footer className="mx-auto max-w-4xl px-6 pb-8 text-center text-xs text-neutral-500">
                    <Link href={show(profile.handle)} className="underline">
                        /creators/{profile.handle}
                    </Link>
                </footer>
            </div>
        </>
    );
}
