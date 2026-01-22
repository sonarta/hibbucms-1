import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import Link from '@tiptap/extension-link';
import {
    Bold,
    Italic,
    List,
    ListOrdered,
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignJustify,
    Link as LinkIcon,
    Quote,
    Heading1,
    Heading2,
    Heading3,
    Strikethrough,
    Code,
} from 'lucide-react';
import { Toggle } from './ui/toggle';
import { Separator } from './ui/separator';

interface RichTextEditorProps {
    content: string;
    onChange: (content: string) => void;
}

export default function RichTextEditor({ content, onChange }: RichTextEditorProps) {
    const editor = useEditor({
        extensions: [
            StarterKit,
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Link.configure({
                openOnClick: false,
            }),
        ],
        content,
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML());
        },
    });

    if (!editor) {
        return null;
    }

    const toolbarItems = [
        {
            icon: <Heading1 className="h-4 w-4" />,
            title: 'Heading 1',
            action: () => editor.chain().focus().toggleHeading({ level: 1 }).run(),
            isActive: () => editor.isActive('heading', { level: 1 }),
        },
        {
            icon: <Heading2 className="h-4 w-4" />,
            title: 'Heading 2',
            action: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
            isActive: () => editor.isActive('heading', { level: 2 }),
        },
        {
            icon: <Heading3 className="h-4 w-4" />,
            title: 'Heading 3',
            action: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
            isActive: () => editor.isActive('heading', { level: 3 }),
        },
        { type: 'separator' },
        {
            icon: <Bold className="h-4 w-4" />,
            title: 'Bold',
            action: () => editor.chain().focus().toggleBold().run(),
            isActive: () => editor.isActive('bold'),
        },
        {
            icon: <Italic className="h-4 w-4" />,
            title: 'Italic',
            action: () => editor.chain().focus().toggleItalic().run(),
            isActive: () => editor.isActive('italic'),
        },
        {
            icon: <Strikethrough className="h-4 w-4" />,
            title: 'Strike',
            action: () => editor.chain().focus().toggleStrike().run(),
            isActive: () => editor.isActive('strike'),
        },
        { type: 'separator' },
        {
            icon: <List className="h-4 w-4" />,
            title: 'Bullet List',
            action: () => editor.chain().focus().toggleBulletList().run(),
            isActive: () => editor.isActive('bulletList'),
        },
        {
            icon: <ListOrdered className="h-4 w-4" />,
            title: 'Ordered List',
            action: () => editor.chain().focus().toggleOrderedList().run(),
            isActive: () => editor.isActive('orderedList'),
        },
        { type: 'separator' },
        {
            icon: <AlignLeft className="h-4 w-4" />,
            title: 'Align Left',
            action: () => editor?.chain().focus().setTextAlign('left').run(),
            isActive: () => editor?.isActive({ textAlign: 'left' }) ?? false,
        },
        {
            icon: <AlignCenter className="h-4 w-4" />,
            title: 'Align Center',
            action: () => editor?.chain().focus().setTextAlign('center').run(),
            isActive: () => editor?.isActive({ textAlign: 'center' }) ?? false,
        },
        {
            icon: <AlignRight className="h-4 w-4" />,
            title: 'Align Right',
            action: () => editor?.chain().focus().setTextAlign('right').run(),
            isActive: () => editor?.isActive({ textAlign: 'right' }) ?? false,
        },
        {
            icon: <AlignJustify className="h-4 w-4" />,
            title: 'Align Justify',
            action: () => editor?.chain().focus().setTextAlign('justify').run(),
            isActive: () => editor?.isActive({ textAlign: 'justify' }) ?? false,
        },
        { type: 'separator' },
        {
            icon: <LinkIcon className="h-4 w-4" />,
            title: 'Link',
            action: () => {
                const url = window.prompt('URL:');
                if (url) {
                    editor?.chain().focus().setLink({ href: url }).run();
                }
            },
            isActive: () => editor?.isActive('link') ?? false,
        },
        {
            icon: <Quote className="h-4 w-4" />,
            title: 'Blockquote',
            action: () => editor.chain().focus().toggleBlockquote().run(),
            isActive: () => editor.isActive('blockquote'),
        },
        {
            icon: <Code className="h-4 w-4" />,
            title: 'Code',
            action: () => editor.chain().focus().toggleCode().run(),
            isActive: () => editor.isActive('code'),
        },
    ];

    return (
        <div className="border rounded-lg overflow-hidden">
            <div className="border-b p-2 flex flex-wrap gap-1 items-center">
                {toolbarItems.map((item, index) => {
                    if (item.type === 'separator') {
                        return <Separator orientation="vertical" className="h-6 " key={index} />;
                    }

                    return (
                        <Toggle
                            key={item.title}
                            pressed={item.isActive?.() ?? false}
                            onPressedChange={() => item.action?.()}
                            title={item.title}
                            className="h-8 w-8 p-0 data-[state=on]:bg-gray-700 data-[state=on]:text-gray-50 data-[state=off]:text-gray-400 data-[state=off]:hover:bg-gray-700 data-[state=off]:hover:text-gray-50 transition-colors rounded-md flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-gray-500"
                        >
                            {item.icon}
                        </Toggle>
                    );
                })}
            </div>

            <EditorContent
                editor={editor}
                className="prose dark:prose-invert max-w-none p-4 min-h-[300px] focus:outline-none [&_.tiptap]:outline-none [&_.tiptap]:min-h-[300px]"
            />
        </div>
    );
}
