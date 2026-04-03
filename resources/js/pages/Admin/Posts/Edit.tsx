import AppLayout from '@/layouts/app-layout';
import Form from './Form';
import { Head } from '@inertiajs/react';

interface Props {
    post: {
        id: number;
        title: string;
        slug: string;
        excerpt: string;
        content: string;
        featured_image: string;
        featured_image_url?: string;
        status: 'draft' | 'published' | 'scheduled' | 'pending_review';
        category_id: number;
        tag_ids: number[];
    };
    preview: {
        url: string;
        signed_url: string;
    };
    categories: {
        id: number;
        name: string;
    }[];
    tags: {
        id: number;
        name: string;
        color: string;
    }[];
    media: {
        id: number;
        name: string;
        url?: string;
    }[];
}

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: route('admin.dashboard'),
    },
    {
        title: 'Posts',
        href: route('admin.posts.index'),
    },
    {
        title: 'Edit',
    },
];
export default function Edit({ post, categories, tags, media, preview }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Post" />
            <div className="p-4">
                <Form post={post} categories={categories} tags={tags} media={media} preview={preview} />
            </div>
        </AppLayout>
    );
}
