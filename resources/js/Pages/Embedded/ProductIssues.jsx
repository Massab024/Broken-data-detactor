import { Box, Card, Page, Text } from '@shopify/polaris';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';

export default function ProductIssues() {
    return (
        <Page title="Product Issues">
            <Card sectioned>
                <Box>
                    <Text variant="headingMd" as="p">
                        Product Issues
                    </Text>
                    <Text as="p">
                        This page will show broken, missing, or incomplete product data once validation is implemented.
                    </Text>
                </Box>
            </Card>
        </Page>
    );
}

ProductIssues.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
