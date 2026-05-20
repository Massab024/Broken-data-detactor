import { Link, router, usePage } from '@inertiajs/react';
import {
    Badge,
    BlockStack,
    Box,
    Button,
    Card,
    Divider,
    IndexTable,
    InlineStack,
    Page,
    Pagination,
    Select,
    Text,
    TextField,
} from '@shopify/polaris';
import { useEffect, useState } from 'react';
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

export default function ProductIssues({
    issues,
    filters,
    filter_options,
    selected_issue,
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [severity, setSeverity] = useState(filters.severity || 'all');
    const [status, setStatus] = useState(filters.status || 'all');
    const [issueKey, setIssueKey] = useState(filters.issue_key || 'all');
    const [healthStatus, setHealthStatus] = useState(filters.health_status || 'all');
    const { query: ziggyQuery } = usePage().props.ziggy;
    const embeddedQuery = { ...(ziggyQuery || {}) };

    delete embeddedQuery.search;
    delete embeddedQuery.severity;
    delete embeddedQuery.status;
    delete embeddedQuery.issue_key;
    delete embeddedQuery.health_status;
    delete embeddedQuery.issue_id;

    useEffect(() => {
        setSearch(filters.search || '');
        setSeverity(filters.severity || 'all');
        setStatus(filters.status || 'all');
        setIssueKey(filters.issue_key || 'all');
        setHealthStatus(filters.health_status || 'all');
    }, [filters]);

    const applyFilters = (page = 1) => {
        router.get(route('product.issues', embeddedQuery), {
            search,
            severity,
            status,
            issue_key: issueKey,
            health_status: healthStatus,
            page,
        }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setSeverity('all');
        setStatus('all');
        setIssueKey('all');
        setHealthStatus('all');
        router.get(route('product.issues', embeddedQuery), {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const currentPage = Number(issues?.current_page || 1);
    const lastPage = Number(issues?.last_page || 1);
    const filterQuery = {
        search,
        severity,
        status,
        issue_key: issueKey,
        health_status: healthStatus,
    };

    const navigatePage = (page) => {
        router.get(route('product.issues', embeddedQuery), { ...filterQuery, page }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <Page title="Product Issues">
            <BlockStack gap="500">
                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingLg" as="h1">
                            Product Issues
                        </Text>
                        <Text as="p" tone="subdued">
                            Review broken product data, filter issue types, and inspect issue details without leaving the embedded app.
                        </Text>
                    </BlockStack>
                </Card>

                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingMd" as="h2">
                            Filters
                        </Text>

                        <Box
                            style={{
                                display: 'grid',
                                gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
                                gap: '16px',
                            }}
                        >
                            <TextField
                                label="Search by product title"
                                value={search}
                                onChange={setSearch}
                                autoComplete="off"
                            />
                            <Select
                                label="Severity"
                                options={filter_options?.severities || []}
                                value={severity}
                                onChange={setSeverity}
                            />
                            <Select
                                label="Issue status"
                                options={filter_options?.statuses || []}
                                value={status}
                                onChange={setStatus}
                            />
                            <Select
                                label="Issue type"
                                options={[{ label: 'All issue types', value: 'all' }, ...(filter_options?.issue_types || [])]}
                                value={issueKey}
                                onChange={setIssueKey}
                            />
                            <Select
                                label="Product health"
                                options={filter_options?.health_statuses || []}
                                value={healthStatus}
                                onChange={setHealthStatus}
                            />
                        </Box>

                        <InlineStack gap="200">
                            <Button variant="primary" onClick={() => applyFilters(1)}>
                                Apply filters
                            </Button>
                            <Button onClick={clearFilters}>Clear</Button>
                        </InlineStack>
                    </BlockStack>
                </Card>

                {selected_issue ? (
                    <Card sectioned>
                        <BlockStack gap="200">
                            <Text variant="headingMd" as="h2">
                                Selected Issue
                            </Text>
                            <InlineStack gap="200">
                                <Text as="span" variant="bodyMd">
                                    {selected_issue.product.title}
                                </Text>
                                <Badge tone={severityTone(selected_issue.severity)}>{selected_issue.severity}</Badge>
                                <Badge tone={statusTone(selected_issue.status)}>{selected_issue.status}</Badge>
                                <Badge tone={healthTone(selected_issue.product.health_status)}>
                                    {selected_issue.product.health_status}
                                </Badge>
                            </InlineStack>
                            <Text as="p" tone="subdued">
                                {selected_issue.message}
                            </Text>
                            <Text as="p" variant="bodySm" tone="subdued">
                                Detected {selected_issue.detected_at} · Last checked {selected_issue.last_checked_at || 'n/a'}
                            </Text>
                        </BlockStack>
                    </Card>
                ) : null}

                <Card sectioned>
                    <BlockStack gap="300">
                        <InlineStack align="space-between">
                            <Text variant="headingMd" as="h2">
                                Issues
                            </Text>
                            <Text as="span" tone="subdued">
                                {issues?.total || 0} total
                            </Text>
                        </InlineStack>

                        {issues?.data?.length ? (
                            <IndexTable
                                resourceName={{ singular: 'issue', plural: 'issues' }}
                                itemCount={issues.data.length}
                                selectedItemsCount={0}
                                onSelectionChange={() => {}}
                                headings={[
                                    { title: 'Product' },
                                    { title: 'Issue' },
                                    { title: 'Severity' },
                                    { title: 'Product Health' },
                                    { title: 'Issue Status' },
                                    { title: 'Detected At' },
                                    { title: 'Last Checked' },
                                    { title: 'Action' },
                                ]}
                            >
                                {issues.data.map((issue, index) => (
                                    <IndexTable.Row id={`issue-${issue.id}`} key={issue.id} position={index}>
                                        <IndexTable.Cell>{issue.product.title}</IndexTable.Cell>
                                        <IndexTable.Cell>{issue.issue_key}</IndexTable.Cell>
                                        <IndexTable.Cell>
                                            <Badge tone={severityTone(issue.severity)}>{issue.severity}</Badge>
                                        </IndexTable.Cell>
                                        <IndexTable.Cell>
                                            <Badge tone={healthTone(issue.product.health_status)}>{issue.product.health_status}</Badge>
                                        </IndexTable.Cell>
                                        <IndexTable.Cell>
                                            <Badge tone={statusTone(issue.status)}>{issue.status}</Badge>
                                        </IndexTable.Cell>
                                        <IndexTable.Cell>{issue.detected_at}</IndexTable.Cell>
                                        <IndexTable.Cell>{issue.last_checked_at || 'n/a'}</IndexTable.Cell>
                                        <IndexTable.Cell>
                                            <Link href={route('product.issues.show', { productIssue: issue.id, ...ziggyQuery })}>
                                                <Button variant="plain">View Details</Button>
                                            </Link>
                                        </IndexTable.Cell>
                                    </IndexTable.Row>
                                ))}
                            </IndexTable>
                        ) : (
                            <Box paddingBlock="6">
                                <Text as="p" tone="subdued">
                                    No issues match the current filters.
                                </Text>
                            </Box>
                        )}

                        <Divider />

                        <Pagination
                            hasPrevious={currentPage > 1}
                            onPrevious={() => navigatePage(currentPage - 1)}
                            hasNext={currentPage < lastPage}
                            onNext={() => navigatePage(currentPage + 1)}
                        />
                    </BlockStack>
                </Card>
            </BlockStack>
        </Page>
    );
}

ProductIssues.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
