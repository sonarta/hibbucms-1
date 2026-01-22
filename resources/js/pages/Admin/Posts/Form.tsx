import { router, Link } from '@inertiajs/react';
import { FormEvent, useState, useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import RichTextEditor from '@/components/RichTextEditor';
import MediaPicker from '@/components/MediaPicker';
import { SaveIndicator } from '@/components/SaveIndicator';
import { useAutoSave } from '@/hooks/useAutoSave';
import { Loader2, History } from 'lucide-react';
import { toast } from 'sonner';

interface Category {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
    color: string;
}

interface Props {
    post?: {
        id: number;
        title: string;
        slug: string;
        excerpt: string;
        content: string;
        featured_image: string;
        status: 'draft' | 'published';
        category_id: number;
        tag_ids: number[];
        featured_image_url?: string;
        featured_image_id?: string;
    };
    categories: Category[];
    tags: Tag[];
    media: { id: number; name: string; url?: string }[];
}


export default function Form({ post, categories, tags, media }: Props) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [values, setValues] = useState({
        title: post?.title || '',
        excerpt: post?.excerpt || '',
        content: post?.content || '',
        featured_image: null as File | null,
        status: post?.status || 'draft',
        category_id: post?.category_id?.toString() || '',
        tag_ids: post?.tag_ids?.map(id => id.toString()) || [],
    });

    const [selectedMediaId, setSelectedMediaId] = useState<string | undefined>(
        post?.featured_image_id?.toString() || undefined
    );

    // Auto-save hook
    const { status: saveStatus, lastSavedAt, error: saveError, triggerSave } = useAutoSave(
        post?.id || null,
        {
            debounceMs: 3000,
            onSuccess: (postId) => {
                // Update URL if this is a new post that just got an ID
                if (!post && postId) {
                    window.history.replaceState({}, '', route('admin.posts.edit', postId));
                }
            },
            onError: (error) => {
                toast.error(error);
            },
        }
    );

    // Track if content has changed from initial values
    const initialValuesRef = useRef({
        title: post?.title || '',
        content: post?.content || '',
        excerpt: post?.excerpt || '',
        category_id: post?.category_id?.toString() || '',
        tag_ids: post?.tag_ids?.map(id => id.toString()) || [],
    });

    // Trigger auto-save when values change
    useEffect(() => {
        const hasChanged =
            values.title !== initialValuesRef.current.title ||
            values.content !== initialValuesRef.current.content ||
            values.excerpt !== initialValuesRef.current.excerpt ||
            values.category_id !== initialValuesRef.current.category_id ||
            JSON.stringify(values.tag_ids) !== JSON.stringify(initialValuesRef.current.tag_ids);

        if (hasChanged) {
            triggerSave({
                title: values.title,
                content: values.content,
                excerpt: values.excerpt,
                category_id: values.category_id || undefined,
                featured_image_id: selectedMediaId,
                tags: values.tag_ids,
            });
        }
    }, [values, selectedMediaId, triggerSave]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        const formData = new FormData();

        formData.append('title', values.title);
        formData.append('excerpt', values.excerpt);
        formData.append('content', values.content);
        formData.append('status', values.status);
        formData.append('category_id', values.category_id);
        values.tag_ids.forEach((tagId) => {
            formData.append('tags[]', tagId);
        });

        // Handle featured image
        if (values.featured_image) {
            formData.append('featured_image', values.featured_image);
        } else if (selectedMediaId) {
            formData.append('featured_image_id', selectedMediaId);
        }

        if (post) {
            formData.append('_method', 'PUT');
            router.post(route('admin.posts.update', post.id), formData, {
                onSuccess: () => {
                    toast.success('Post updated successfully');
                    setIsSubmitting(false);
                },
                onError: () => {
                    toast.error('Failed to update post');
                    setIsSubmitting(false);
                }
            });
        } else {
            router.post(route('admin.posts.store'), formData, {
                onSuccess: () => {
                    toast.success('Post created successfully');
                    setIsSubmitting(false);
                },
                onError: () => {
                    toast.error('Failed to create post');
                    setIsSubmitting(false);
                }
            });
        }
    };

    const handleTagChange = (selectedTags: string[]) => {
        setValues({ ...values, tag_ids: selectedTags });
    };

    return (
            <div className="p-4">
                <div className="flex justify-between items-center mb-4">
                    <h1 className="text-2xl font-bold">{post ? 'Edit Post' : 'Create New Post'}</h1>
                    <div className="flex items-center gap-4">
                        {post && (
                            <Link href={route('admin.posts.revisions', post.id)}>
                                <Button variant="outline" size="sm">
                                    <History className="h-4 w-4 mr-2" />
                                    Revisions
                                </Button>
                            </Link>
                        )}
                        <SaveIndicator
                            status={saveStatus}
                            lastSavedAt={lastSavedAt}
                            error={saveError}
                        />
                    </div>
                </div>

                <div className="flex gap-4">
                    {/* Main Content */}
                    <div className="flex-1 space-y-4">
                        <div className="rounded-lg p-4 border space-y-4">
                            <div>
                                <Input
                                    id="title"
                                    type="text"
                                    value={values.title}
                                    onChange={(e) =>
                                        setValues({
                                            ...values,
                                            title: e.target.value,
                                        })
                                    }
                                    placeholder="Enter post title"
                                    required
                                />
                            </div>

                            <div>
                                <RichTextEditor
                                    content={values.content}
                                    onChange={(content) =>
                                        setValues({
                                            ...values,
                                            content,
                                        })
                                    }
                                />
                            </div>

                            <div>
                                <Label htmlFor="excerpt" className=" mb-2 block">Excerpt</Label>
                                <Textarea
                                    id="excerpt"
                                    value={values.excerpt}
                                    onChange={(e) =>
                                        setValues({
                                            ...values,
                                            excerpt: e.target.value,
                                        })
                                    }
                                    placeholder="Enter post excerpt"
                                    className="min-h-[100px]  placeholder:text-gray-500"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="w-80 space-y-4">
                        <div className="rounded-lg p-4 border space-y-4">
                            <div className="flex justify-between items-center">
                                <Button
                                    type="button"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        handleSubmit(e as unknown as FormEvent);
                                    }}
                                    disabled={isSubmitting}
                                >
                                    {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    {post ? 'Update Post' : 'Create Post'}
                                </Button>

                                <Select
                                    value={values.status}
                                    onValueChange={(value: 'draft' | 'published') =>
                                        setValues({
                                            ...values,
                                            status: value,
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-32 ">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft" className=" hover:bg-gray-800">Draft</SelectItem>
                                        <SelectItem value="published" className=" hover:bg-gray-800">Published</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="rounded-lg p-4 border space-y-4">
                            <h3 className="font-semibold ">Category</h3>
                            <Select
                                value={values.category_id}
                                onValueChange={(value) =>
                                    setValues({
                                        ...values,
                                        category_id: value,
                                    })
                                }
                            >
                                <SelectTrigger className="">
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem
                                            key={category.id}
                                            value={category.id.toString()}
                                            className=" hover:bg-gray-800"
                                        >
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="rounded-lg p-4 border space-y-4">
                            <h3 className="font-semibold ">Tags</h3>
                            <div className="flex flex-wrap gap-2">
                                {tags.map((tag) => (
                                    <label
                                        key={tag.id}
                                        className="flex items-center space-x-2 p-2 border rounded cursor-pointer transition-colors"

                                    >
                                        <input
                                            type="checkbox"
                                            value={tag.id}
                                            checked={values.tag_ids.includes(tag.id.toString())}
                                            onChange={(e) => {
                                                const tagId = e.target.value;
                                                if (e.target.checked) {
                                                    handleTagChange([...values.tag_ids, tagId]);
                                                } else {
                                                    handleTagChange(
                                                        values.tag_ids.filter((id) => id !== tagId)
                                                    );
                                                }
                                            }}
                                            className="mr-2"
                                        />
                                        {tag.name}
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-lg p-4 border space-y-4">
                            <h3 className="font-semibold ">Featured Image</h3>
                            <MediaPicker
                                media={media}
                                selectedMediaId={selectedMediaId}
                                onSelect={(mediaId) => {
                                    setSelectedMediaId(mediaId);
                                    setValues({ ...values, featured_image: null });
                                }}
                                onUpload={(file) => {
                                    setValues({ ...values, featured_image: file });
                                    setSelectedMediaId(undefined);
                                }}
                                featuredImageUrl={post?.featured_image_url}
                            />
                        </div>
                    </div>
                </div>
            </div>
    );
}
