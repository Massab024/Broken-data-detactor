import { Box, Button } from '@shopify/polaris';
import { Link } from '@inertiajs/react';
import { Toaster } from 'react-hot-toast';

const navigationItems = [
    { label: 'Dashboard', route: 'home' },
    { label: 'Product Issues', route: 'product.issues' },
    { label: 'Validation Settings', route: 'validation.settings' },
    { label: 'Logs', route: 'logs' },
];

export default function AuthenticatedLayout({ children }) {
    return (
        <div style={{ minHeight: '100vh', backgroundColor: '#F6F8FA' }}>
            <Toaster position="top-right" reverseOrder={false} />
            <Box paddingInline="8" paddingBlock="5">
                {/* <Box as="nav" paddingBlockEnd="4">
                        {navigationItems.map((item) => (
                            <Link key={item.route} href={route(item.route)}>
                                <Button plain>{item.label}</Button>
                            </Link>
                        ))}
                </Box> */}
                <Box>{children}</Box>
            </Box>
        </div>
    );
}
