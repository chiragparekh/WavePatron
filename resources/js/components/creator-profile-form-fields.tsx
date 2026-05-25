import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    creatorProfileVisibilityOptions,
    type CreatorProfileVisibility,
} from '@/types/creator-profile-visibility';

type CreatorProfileFormFieldsProps = {
    defaults?: {
        handle?: string;
        display_name?: string;
        tagline?: string;
        bio?: string;
        categories?: string[];
        website?: string;
        support_email?: string;
        visibility?: CreatorProfileVisibility;
        avatar_url?: string | null;
        cover_image_url?: string | null;
    };
    errors: Record<string, string>;
};

export default function CreatorProfileFormFields({
    defaults = {},
    errors,
}: CreatorProfileFormFieldsProps) {
    const categoriesValue = (defaults.categories ?? []).join(', ');

    return (
        <div className="grid gap-6">
            <div className="grid gap-2">
                <Label htmlFor="handle">Handle</Label>
                <Input
                    id="handle"
                    name="handle"
                    defaultValue={defaults.handle}
                    required
                    placeholder="your-handle"
                    autoComplete="off"
                />
                <p className="text-muted-foreground text-xs">
                    Lowercase letters, numbers, and hyphens only. Used in your
                    public URL.
                </p>
                <InputError message={errors.handle} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="display_name">Display name</Label>
                <Input
                    id="display_name"
                    name="display_name"
                    defaultValue={defaults.display_name}
                    required
                />
                <InputError message={errors.display_name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="tagline">Tagline</Label>
                <Input
                    id="tagline"
                    name="tagline"
                    defaultValue={defaults.tagline ?? ''}
                />
                <InputError message={errors.tagline} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="bio">About</Label>
                <Textarea
                    id="bio"
                    name="bio"
                    defaultValue={defaults.bio ?? ''}
                    rows={5}
                />
                <InputError message={errors.bio} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="categories">Categories</Label>
                <Input
                    id="categories"
                    name="categories"
                    defaultValue={categoriesValue}
                    placeholder="music, podcast, education"
                />
                <p className="text-muted-foreground text-xs">
                    Comma-separated tags.
                </p>
                <InputError message={errors.categories} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="website">Website</Label>
                <Input
                    id="website"
                    name="website"
                    type="url"
                    defaultValue={defaults.website ?? ''}
                    placeholder="https://"
                />
                <InputError message={errors.website} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="support_email">Support email</Label>
                <Input
                    id="support_email"
                    name="support_email"
                    type="email"
                    defaultValue={defaults.support_email ?? ''}
                />
                <InputError message={errors.support_email} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="visibility">Visibility</Label>
                <select
                    id="visibility"
                    name="visibility"
                    defaultValue={defaults.visibility ?? 'hidden'}
                    className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    {creatorProfileVisibilityOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.visibility} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="avatar">Avatar</Label>
                {defaults.avatar_url ? (
                    <img
                        src={defaults.avatar_url}
                        alt=""
                        className="size-16 rounded-full object-cover"
                    />
                ) : null}
                <Input id="avatar" name="avatar" type="file" accept="image/*" />
                <InputError message={errors.avatar} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="cover_image">Cover image</Label>
                {defaults.cover_image_url ? (
                    <img
                        src={defaults.cover_image_url}
                        alt=""
                        className="h-24 w-full rounded-lg object-cover"
                    />
                ) : null}
                <Input
                    id="cover_image"
                    name="cover_image"
                    type="file"
                    accept="image/*"
                />
                <InputError message={errors.cover_image} />
            </div>
        </div>
    );
}
