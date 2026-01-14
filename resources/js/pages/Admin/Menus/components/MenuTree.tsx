import { useState } from "react";
import { ReactSortable } from "react-sortablejs";
import { MenuItemData } from "./types";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    GripVertical,
    FileText,
    LinkIcon,
    Home,
    ExternalLink,
    Trash,
    ChevronDown,
    ChevronRight
} from "lucide-react";

interface MenuTreeProps {
    items: MenuItemData[];
    setItems: (items: MenuItemData[]) => void;
    parentId?: string | number | null;
    onUpdateItem: (id: string | number, data: any) => void;
    onRemoveItem: (id: string | number) => void;
    activeMenu: string | number;
    onReorder: (items: any[]) => void;
    className?: string;
}

interface MenuItemProps {
    item: MenuItemData;
    onUpdate: (id: string | number, data: any) => void;
    onRemove: (id: string | number) => void;
    activeMenu: string | number;
    onReorder: (items: any[]) => void;
}

function MenuItem({ item, onUpdate, onRemove, activeMenu, onReorder }: MenuItemProps) {
    const [isOpen, setIsOpen] = useState(false);

    const handleUpdateChildren = (newChildren: MenuItemData[]) => {
        onUpdate(item.id, { children: newChildren });

        const itemsToUpdate = newChildren.map((child, idx) => ({
            id: child.id,
            order: idx,
            parent_id: item.id
        }));

        if (itemsToUpdate.length > 0) {
            onReorder(itemsToUpdate);
        }
    };

    return (
        <div
            className="flex flex-col border rounded-md bg-card mb-2 transition-all duration-200 shadow-sm"
            data-id={item.id.toString()}
        >
            <div className={`flex items-center gap-3 p-3 rounded-md hover:bg-accent/50 ${isOpen ? 'bg-accent/30' : ''}`}>
                <div className="text-muted-foreground cursor-move hover:text-foreground transition-colors">
                    <GripVertical className="h-5 w-5" />
                </div>

                <div className="flex-1 flex items-center gap-3 cursor-pointer" onClick={() => setIsOpen(!isOpen)}>
                    <div className="flex items-center gap-2">
                        {item.type === "page" && <FileText className="h-4 w-4 text-blue-500" />}
                        {item.type === "post" && <FileText className="h-4 w-4 text-green-500" />}
                        {item.type === "custom" && <LinkIcon className="h-4 w-4 text-orange-500" />}
                        {item.type === "home" && <Home className="h-4 w-4 text-purple-500" />}
                        <span className="font-medium text-sm">{item.title}</span>
                    </div>
                </div>

                <div className="flex items-center">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8 text-muted-foreground"
                        onClick={() => setIsOpen(!isOpen)}
                    >
                        {isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                        onClick={() => onRemove(item.id)}
                    >
                        <Trash className="h-4 w-4" />
                    </Button>
                </div>
            </div>

            {isOpen && (
                <div className="px-4 pb-4 pt-0 animate-in slide-in-from-top-2 duration-200">
                    <div className="pt-4 border-t space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label className="text-xs">Navigation Label</Label>
                                <Input
                                    value={item.title}
                                    onChange={(e) => onUpdate(item.id, { title: e.target.value })}
                                    className="h-8 text-sm"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label className="text-xs">URL</Label>
                                <Input
                                    value={item.url}
                                    onChange={(e) => onUpdate(item.id, { url: e.target.value })}
                                    className="h-8 text-sm"
                                />
                            </div>
                        </div>
                        <div className="flex items-center justify-between">
                            <div className="text-xs text-muted-foreground capitalize">
                                Type: {item.type}
                            </div>
                            <Button
                                variant={item.target === '_blank' ? "secondary" : "outline"}
                                size="sm"
                                onClick={() => onUpdate(item.id, { target: item.target === '_blank' ? '_self' : '_blank' })}
                                className="h-7 text-xs"
                            >
                                <ExternalLink className="h-3 w-3 mr-1.5" />
                                {item.target === '_blank' ? 'Opens in new tab' : 'Opens in same tab'}
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            <div className="pl-8 pr-2 pb-2">
                <MenuTree
                    items={item.children || []}
                    setItems={handleUpdateChildren}
                    parentId={item.id}
                    onUpdateItem={onUpdate}
                    onRemoveItem={onRemove}
                    activeMenu={activeMenu}
                    onReorder={onReorder}
                    className={(!item.children || item.children.length === 0) ? "empty-dropzone" : ""}
                />
            </div>
        </div>
    );
}

export default function MenuTree({
    items,
    setItems,
    parentId = null,
    onUpdateItem,
    onRemoveItem,
    activeMenu,
    onReorder,
    className
}: MenuTreeProps) {
    return (
        <ReactSortable
            list={items}
            setList={(newItems) => setItems(newItems)}
            group="nested-menu"
            animation={200}
            fallbackOnBody={true}
            swapThreshold={0.65}
            ghostClass="sortable-ghost"
            chosenClass="sortable-chosen"
            dragClass="sortable-drag"
            handle=".cursor-move"
            className={`space-y-2 min-h-[10px] ${className || ''}`}
            data-parent-id={parentId}
            data-list-id={parentId ? `child-${parentId}` : 'main'}
        >
            {items.map((item) => (
                <MenuItem
                    key={item.id}
                    item={item}
                    onUpdate={onUpdateItem}
                    onRemove={onRemoveItem}
                    activeMenu={activeMenu}
                    onReorder={onReorder}
                />
            ))}
        </ReactSortable>
    );
}
