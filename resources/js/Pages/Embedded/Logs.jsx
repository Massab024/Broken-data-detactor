import { Box, Card, Page, Text } from '@shopify/polaris';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';

export default function Logs() {
    return (
        <Page title="Logs">
            <Card sectioned>
                <Box>
                    <Text variant="headingMd" as="p">
                        Logs
                    </Text>
                    <Text as="p">
                        Application logs and sync activity will appear here.
                    </Text>
                </Box>
            </Card>
        </Page>
    );
}

Logs.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
