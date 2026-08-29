import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

export default function FlashNotification() {
    const page = usePage<{ flash: { success?: string }; errors: Record<string, string> }>();
    const errorMessage = useMemo(() => Object.values(page.props.errors ?? {})[0], [page.props.errors]);
    const message = page.props.flash.success ?? errorMessage;
    const type = page.props.flash.success ? 'success' : 'error';
    const [visible, setVisible] = useState(Boolean(message));

    useEffect(() => {
        setVisible(Boolean(message));
        if (!message) return;
        const timeout = window.setTimeout(() => setVisible(false), 3500);
        return () => window.clearTimeout(timeout);
    }, [message]);

    if (!message || !visible) return null;

    return <div className={`flash-toast ${type}`} role="status">{type === 'success' ? <CheckCircle2/> : <AlertCircle/>}<span>{message}</span><button onClick={() => setVisible(false)} aria-label="Dismiss notification"><X/></button></div>;
}
