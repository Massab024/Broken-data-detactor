import { Box, Card, Page, Text } from '@shopify/polaris';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';

export default function ValidationSettings() {
    return (
        <Page title="Validation Settings">
            <Card sectioned>
                <Box>
                    <Text variant="headingMd" as="p">
                        Validation Settings
                    </Text>
                    <Text as="p">
                        Configure the rules used to detect broken product data in your Shopify store.
                    </Text>
                </Box>
            </Card>
        </Page>
    );
}

ValidationSettings.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
