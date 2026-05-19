import { Box, Button, Card, Page, Text } from '@shopify/polaris';
import { Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';

export default function Dashboard() {
    return (
        <Page title="Dashboard">
            <Card sectioned>
                {/* <Stack vertical spacing="extraLoose"> */}
                    <Text variant="headingMd" as="p">
                        Welcome to Broken Data Detector
                    </Text>
                    <Text as="p">
                        Monitor and detect broken or incomplete Shopify product data from a clean Polaris dashboard.
                    </Text>
                    <Box paddingBlockStart="4">
                        <Link href={route('product.issues')}>
                            <Button primary>View Product Issues</Button>
                        </Link>
                    </Box>
                {/* </Stack> */}
            </Card>
        </Page>
    );
}

Dashboard.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
