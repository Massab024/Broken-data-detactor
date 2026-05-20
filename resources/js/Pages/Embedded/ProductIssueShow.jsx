import { Link, router, usePage } from '@inertiajs/react';
import {
    Badge,
    BlockStack,
    Box,
    Button,
    Card,
    Divider,
    InlineStack,
    IndexTable,
    Page,
    Text,
} from '@shopify/polaris';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/Embedded/AuthenticatedLayout';

function severityTone(severity) {
    if (severity === 'critical') return 'critical';
    if (severity === 'high') return 'warning';
    if (severity === 'medium') return 'attention';
    return 'info';
}

function statusTone(status) {
    return status === 'resolved' ? 'success' : 'critical';
}

function healthTone(healthStatus) {
    if (healthStatus === 'healthy') return 'success';
    if (healthStatus === 'warning') return 'warning';
    if (healthStatus === 'critical') return 'critical';
    return 'info';
}

function DetailRow({ label, value }) {
    return (
        <InlineStack align="space-between" blockAlign="start">
            <Text as="span" tone="subdued">{label}</Text>
            <Text as="span" alignment="end">{value || 'n/a'}</Text>
        </InlineStack>
    );
}

export default function ProductIssueShow({
    product_issue,
    product,
    variants,
    open_issues,
    resolved_issues,
    validation_rule,
    shopify_admin_url,
}) {
    const { query } = usePage().props.ziggy;
    const [rechecking, setRechecking] = useState(false);

    const handleRecheck = () => {
        setRechecking(true);
        router.post(route('products.validate.single', { product: product.id, ...query }), {}, {
            preserveScroll: true,
            onFinish: () => setRechecking(false),
        });
    };

    return (
        <Page
            title="Product Issue Details"
            backAction={{ content: 'Back to Product Issues', url: route('product.issues', query) }}
        >
            <BlockStack gap="500">
                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingLg" as="h1">
                            Product Issue Details
                        </Text>
                        <Text as="p" tone="subdued">
                            Inspect the selected issue, review all related product data, and recheck the product after you fix it in Shopify.
                        </Text>
                    </BlockStack>
                </Card>

                <Box
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))',
                        gap: '16px',
                    }}
                >
                    <Card sectioned>
                        <BlockStack gap="300">
                            <Text variant="headingMd" as="h2">Product Summary</Text>
                            <InlineStack gap="300" blockAlign="start">
                                {product.image_url ? (
                                    <img
                                        src={product.image_url}
                                        alt={product.title || 'Product image'}
                                        style={{ width: 96, height: 96, objectFit: 'cover', borderRadius: 12 }}
                                    />
                                ) : null}
                                <BlockStack gap="150" grow>
                                    <DetailRow label="Product title" value={product.title} />
                                    <DetailRow label="Shopify product ID" value={String(product.shopify_product_id)} />
                                    <DetailRow label="Handle" value={product.handle} />
                                    <DetailRow label="Vendor" value={product.vendor} />
                                    <DetailRow label="Product type" value={product.product_type || 'n/a'} />
                                    <InlineStack align="space-between">
                                        <Text as="span" tone="subdued">Status</Text>
                                        <Badge tone="info">{product.status || 'n/a'}</Badge>
                                    </InlineStack>
                                    <InlineStack align="space-between">
                                        <Text as="span" tone="subdued">Health status</Text>
                                        <Badge tone={healthTone(product.health_status)}>{product.health_status}</Badge>
                                    </InlineStack>
                                    <DetailRow label="Last synced" value={product.last_synced_at} />
                                    <DetailRow label="Last checked" value={product.last_checked_at} />
                                </BlockStack>
                            </InlineStack>
                        </BlockStack>
                    </Card>

                    <Card sectioned>
                        <BlockStack gap="300">
                            <Text variant="headingMd" as="h2">Selected Issue</Text>
                            <InlineStack gap="200">
                                <Badge tone={severityTone(product_issue.severity)}>{product_issue.severity}</Badge>
                                <Badge tone={statusTone(product_issue.status)}>{product_issue.status}</Badge>
                            </InlineStack>
                            <DetailRow label="Issue key" value={product_issue.issue_key} />
                            <DetailRow label="Message" value={product_issue.message} />
                            <DetailRow label="Suggested fix" value={validation_rule?.description || product_issue.metadata?.suggested_fix} />
                            <DetailRow label="Detected at" value={product_issue.detected_at} />
                            <DetailRow label="Resolved at" value={product_issue.resolved_at} />

                            {validation_rule ? (
                                <>
                                    <Divider />
                                    <BlockStack gap="150">
                                        <Text variant="headingSm" as="h3">Matching Validation Rule</Text>
                                        <DetailRow label="Rule name" value={validation_rule.name} />
                                        <DetailRow label="Rule key" value={validation_rule.rule_key} />
                                        <DetailRow label="Severity" value={validation_rule.severity} />
                                        <DetailRow label="Enabled" value={validation_rule.is_enabled ? 'Yes' : 'No'} />
                                    </BlockStack>
                                </>
                            ) : null}
                        </BlockStack>
                    </Card>
                </Box>

                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingMd" as="h2">Actions</Text>
                        <InlineStack gap="200" wrap={true}>
                            <Button onClick={() => window.open(shopify_admin_url, '_blank' )} disabled={shopify_admin_url === '#'}>
                                Open Product in Shopify
                            </Button>
                            <Button variant="primary" onClick={handleRecheck} loading={rechecking}>
                                Recheck Product
                            </Button>
                            {/* <Link href={route('product.issues', query)}>
                                <Button variant="plain">Back to Product Issues</Button>
                            </Link> */}
                        </InlineStack>
                    </BlockStack>
                </Card>

                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingMd" as="h2">All Open Issues for This Product</Text>
                        {open_issues.length ? (
                            <IndexTable
                                resourceName={{ singular: 'issue', plural: 'issues' }}
                                itemCount={open_issues.length}
                                selectedItemsCount={0}
                                onSelectionChange={() => {}}
                                headings={[
                                    { title: 'Issue Key' },
                                    { title: 'Severity' },
                                    { title: 'Message' },
                                    { title: 'Detected At' },
                                ]}
                            >
                                {open_issues.map((issue, index) => (
                                    <IndexTable.Row id={`open-issue-${issue.id}`} key={issue.id} position={index}>
                                        <IndexTable.Cell>{issue.issue_key}</IndexTable.Cell>
                                        <IndexTable.Cell><Badge tone={severityTone(issue.severity)}>{issue.severity}</Badge></IndexTable.Cell>
                                        <IndexTable.Cell>{issue.message}</IndexTable.Cell>
                                        <IndexTable.Cell>{issue.detected_at}</IndexTable.Cell>
                                    </IndexTable.Row>
                                ))}
                            </IndexTable>
                        ) : (
                            <Text as="p" tone="subdued">No open issues for this product.</Text>
                        )}
                    </BlockStack>
                </Card>

                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingMd" as="h2">Resolved Issues for This Product</Text>
                        {resolved_issues.length ? (
                            <IndexTable
                                resourceName={{ singular: 'issue', plural: 'issues' }}
                                itemCount={resolved_issues.length}
                                selectedItemsCount={0}
                                onSelectionChange={() => {}}
                                headings={[
                                    { title: 'Issue Key' },
                                    { title: 'Severity' },
                                    { title: 'Message' },
                                    { title: 'Resolved At' },
                                ]}
                            >
                                {resolved_issues.map((issue, index) => (
                                    <IndexTable.Row id={`resolved-issue-${issue.id}`} key={issue.id} position={index}>
                                        <IndexTable.Cell>{issue.issue_key}</IndexTable.Cell>
                                        <IndexTable.Cell><Badge tone={severityTone(issue.severity)}>{issue.severity}</Badge></IndexTable.Cell>
                                        <IndexTable.Cell>{issue.message}</IndexTable.Cell>
                                        <IndexTable.Cell>{issue.resolved_at}</IndexTable.Cell>
                                    </IndexTable.Row>
                                ))}
                            </IndexTable>
                        ) : (
                            <Text as="p" tone="subdued">No resolved issues for this product.</Text>
                        )}
                    </BlockStack>
                </Card>

                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingMd" as="h2">Variants</Text>
                        {variants.length ? (
                            <IndexTable
                                resourceName={{ singular: 'variant', plural: 'variants' }}
                                itemCount={variants.length}
                                selectedItemsCount={0}
                                onSelectionChange={() => {}}
                                headings={[
                                    { title: 'Title' },
                                    { title: 'SKU' },
                                    { title: 'Price' },
                                    { title: 'Inventory Quantity' },
                                    { title: 'Shopify Variant ID' },
                                ]}
                            >
                                {variants.map((variant, index) => (
                                    <IndexTable.Row id={`variant-${variant.id}`} key={variant.id} position={index}>
                                        <IndexTable.Cell>{variant.title}</IndexTable.Cell>
                                        <IndexTable.Cell>{variant.sku || 'n/a'}</IndexTable.Cell>
                                        <IndexTable.Cell>{variant.price ?? 'n/a'}</IndexTable.Cell>
                                        <IndexTable.Cell>{variant.inventory_quantity ?? 'n/a'}</IndexTable.Cell>
                                        <IndexTable.Cell>{variant.shopify_variant_id}</IndexTable.Cell>
                                    </IndexTable.Row>
                                ))}
                            </IndexTable>
                        ) : (
                            <Text as="p" tone="subdued">No variants found for this product.</Text>
                        )}
                    </BlockStack>
                </Card>
            </BlockStack>
        </Page>
    );
}

ProductIssueShow.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
