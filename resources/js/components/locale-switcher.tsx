import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Globe } from 'lucide-react';

export function LocaleSwitcher() {
    const { locale, translations } = usePage<SharedData>().props;
    const c = translations.common;

    const setLocale = (next: 'en' | 'id') => {
        if (next === locale) {
            return;
        }
        router.post(route('locale.switch', { locale: next }));
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="size-9" aria-label="Language">
                    <Globe className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => setLocale('en')}>
                    {c.locale_en ?? 'English'}
                    {locale === 'en' ? ' ✓' : ''}
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => setLocale('id')}>
                    {c.locale_id ?? 'Bahasa Indonesia'}
                    {locale === 'id' ? ' ✓' : ''}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
