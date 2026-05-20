import { router, Link } from '@inertiajs/react';
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

function StatCard({ label, value, tone = 'base' }) {
    return (
        <Card sectioned>
            <BlockStack gap="200">
                <Text as="p" variant="bodyMd" tone="subdued">
                    {label}
                </Text>
                <Text as="p" variant="heading2xl">
                    {value}
                </Text>
                <Badge tone={tone === 'critical' ? 'critical' : tone === 'warning' ? 'warning' : tone === 'success' ? 'success' : 'info'}>
                    {label}
                </Badge>
            </BlockStack>
        </Card>
    );
}

function severityTone(severity) {
    if (severity === 'critical') return 'critical';
    if (severity === 'high') return 'warning';
    if (severity === 'medium') return 'attention';
    return 'info';
}

function statusTone(status) {
    return status === 'resolved' ? 'success' : 'critical';
}

export default function Dashboard({
    total_products_scanned,
    healthy_products_count,
    warning_products_count,
    critical_products_count,
    needs_review_products_count,
    open_issues_count,
    resolved_issues_count,
    recently_detected_issues,
    recent_activity_logs,
    issue_count_by_severity,
    product_count_by_health_status,
    enabled_validation_rules_count,
    disabled_validation_rules_count,
}) {
    const [syncing, setSyncing] = useState(false);
    const [validating, setValidating] = useState(false);

    const handleSync = () => {
        setSyncing(true);
        router.post(route('products.sync'), {}, {
            preserveScroll: true,
            onFinish: () => setSyncing(false),
        });
    };

    const handleValidate = () => {
        setValidating(true);
        router.post(route('products.validate'), {}, {
            preserveScroll: true,
            onFinish: () => setValidating(false),
        });
    };

    const healthCards = [
        { label: 'Total Products', value: total_products_scanned, tone: 'info' },
        { label: 'Healthy', value: healthy_products_count, tone: 'success' },
        { label: 'Warnings', value: warning_products_count, tone: 'warning' },
        { label: 'Critical', value: critical_products_count, tone: 'critical' },
        { label: 'Needs Review', value: needs_review_products_count, tone: 'info' },
    ];

    const issueSeverityRows = Object.entries(issue_count_by_severity || {}).map(([key, value]) => (
        <InlineStack key={key} align="space-between">
            <Text as="span" tone="subdued">
                {key.replace('_', ' ')}
            </Text>
            <Text as="span">{value}</Text>
        </InlineStack>
    ));

    const productHealthRows = Object.entries(product_count_by_health_status || {}).map(([key, value]) => (
        <InlineStack key={key} align="space-between">
            <Text as="span" tone="subdued">
                {key.replace('_', ' ')}
            </Text>
            <Text as="span">{value}</Text>
        </InlineStack>
    ));

    const hasProducts = Number(total_products_scanned) > 0;

    return (
        <Page title="Broken Data Detector">
            <BlockStack gap="500">
                <Card sectioned>
                    <BlockStack gap="400">
                        <BlockStack gap="100">
                            <Text variant="headingLg" as="h1">
                                Broken Data Detector
                            </Text>
                            <Text as="p" tone="subdued">
                                Monitor Shopify products, detect missing or invalid data, and resolve issues without leaving the embedded app.
                            </Text>
                        </BlockStack>

                        <InlineStack gap="300">
                            <Button variant="primary" onClick={handleSync} loading={syncing}>
                                Sync Products
                            </Button>
                            <Button onClick={handleValidate} loading={validating}>
                                Run Validation
                            </Button>
                            <Link href={route('product.issues')}>
                                <Button variant="plain">View Product Issues</Button>
                            </Link>
                        </InlineStack>
                    </BlockStack>
                </Card>

                {!hasProducts ? (
                    <Card sectioned>
                        <Box padding="6">
                            <BlockStack gap="300" align="center">
                                <Text variant="headingMd" as="h2">
                                    No products scanned yet
                                </Text>
                                <Text as="p" tone="subdued">
                                    Sync products from Shopify to start broken data detection.
                                </Text>
                                <Button variant="primary" onClick={handleSync} loading={syncing}>
                                    Sync Products
                                </Button>
                            </BlockStack>
                        </Box>
                    </Card>
                ) : null}

                <Box
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
                        gap: '16px',
                    }}
                >
                    {healthCards.map((card) => (
                        <StatCard key={card.label} {...card} />
                    ))}
                </Box>

                <Box
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))',
                        gap: '16px',
                    }}
                >
                    <Card sectioned>
                        <BlockStack gap="300">
                            <Text variant="headingMd" as="h2">
                                Issue Summary
                            </Text>
                            <InlineStack align="space-between">
                                <Text as="span" tone="subdued">
                                    Open issues
                                </Text>
                                <Text as="span">{open_issues_count}</Text>
                            </InlineStack>
                            <InlineStack align="space-between">
                                <Text as="span" tone="subdued">
                                    Resolved issues
                                </Text>
                                <Text as="span">{resolved_issues_count}</Text>
                            </InlineStack>
                            <Divider />
                            <Text as="p" variant="bodySm" tone="subdued">
                                By severity
                            </Text>
                            {issueSeverityRows}
                        </BlockStack>
                    </Card>

                    <Card sectioned>
                        <BlockStack gap="300">
                            <Text variant="headingMd" as="h2">
                                Validation Rules
                            </Text>
                            <InlineStack align="space-between">
                                <Text as="span" tone="subdued">
                                    Enabled rules
                                </Text>
                                <Text as="span">{enabled_validation_rules_count}</Text>
                            </InlineStack>
                            <InlineStack align="space-between">
                                <Text as="span" tone="subdued">
                                    Disabled rules
                                </Text>
                                <Text as="span">{disabled_validation_rules_count}</Text>
                            </InlineStack>
                            <Divider />
                            <Text as="p" variant="bodySm" tone="subdued">
                                Products by health status
                            </Text>
                            {productHealthRows}
                        </BlockStack>
                    </Card>
                </Box>

                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingMd" as="h2">
                            Recently Detected Issues
                        </Text>

                        {recently_detected_issues?.length ? (
                            <IndexTable
                                resourceName={{ singular: 'issue', plural: 'issues' }}
                                itemCount={recently_detected_issues.length}
                                selectedItemsCount={0}
                                onSelectionChange={() => {}}
                                headings={[
                                    { title: 'Product' },
                                    { title: 'Issue' },
                                    { title: 'Severity' },
                                    { title: 'Status' },
                                    { title: 'Detected At' },
                                    { title: 'Action' },
                                ]}
                            >
                                {recently_detected_issues.map((issue, index) => (
                                    <IndexTable.Row id={`recent-issue-${issue.id}`} key={issue.id} position={index}>
                                        <IndexTable.Cell>{issue.product?.title}</IndexTable.Cell>
                                        <IndexTable.Cell>{issue.issue_key}</IndexTable.Cell>
                                        <IndexTable.Cell>
                                            <Badge tone={severityTone(issue.severity)}>{issue.severity}</Badge>
                                        </IndexTable.Cell>
                                        <IndexTable.Cell>
                                            <Badge tone={statusTone(issue.status)}>{issue.status}</Badge>
                                        </IndexTable.Cell>
                                        <IndexTable.Cell>{issue.detected_at}</IndexTable.Cell>
                                        <IndexTable.Cell>
                                            <Link href={issue.view_url}>
                                                <Button variant="plain">View Details</Button>
                                            </Link>
                                        </IndexTable.Cell>
                                    </IndexTable.Row>
                                ))}
                            </IndexTable>
                        ) : (
                            <Text as="p" tone="subdued">
                                No issues detected yet.
                            </Text>
                        )}
                    </BlockStack>
                </Card>

                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingMd" as="h2">
                            Recent Activity Logs
                        </Text>

                        {recent_activity_logs?.length ? (
                            <BlockStack gap="300">
                                {recent_activity_logs.map((log) => (
                                    <Card key={`${log.title}-${log.date}`} sectioned>
                                        <BlockStack gap="100">
                                            <InlineStack align="space-between">
                                                <Text as="p" variant="bodyMd">
                                                    {log.title}
                                                </Text>
                                                <Badge tone="info">{log.type}</Badge>
                                            </InlineStack>
                                            <Text as="p" tone="subdued">
                                                {log.message}
                                            </Text>
                                            <Text as="p" variant="bodySm" tone="subdued">
                                                {log.date}
                                            </Text>
                                        </BlockStack>
                                    </Card>
                                ))}
                            </BlockStack>
                        ) : (
                            <Text as="p" tone="subdued">
                                No activity logs yet.
                            </Text>
                        )}
                    </BlockStack>
                </Card>
            </BlockStack>
        </Page>
    );
}

Dashboard.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
