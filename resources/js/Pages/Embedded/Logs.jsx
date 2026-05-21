import { router, usePage } from '@inertiajs/react';
import {
    BlockStack,
    Box,
    Button,
    Card,
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

function levelTone(level) {
    if (level === 'error') return 'critical';
    if (level === 'warning') return 'warning';
    return 'info';
}

export default function Logs({ logs, filters, filter_options }) {
    const [search, setSearch] = useState(filters.search || '');
    const [event, setEvent] = useState(filters.event || 'all');
    const [level, setLevel] = useState(filters.level || 'all');
    const [date, setDate] = useState(filters.date || '');
    const { query: ziggyQuery } = usePage().props.ziggy;
    const embeddedQuery = { ...(ziggyQuery || {}) };

    delete embeddedQuery.search;
    delete embeddedQuery.event;
    delete embeddedQuery.level;
    delete embeddedQuery.date;

    useEffect(() => {
        setSearch(filters.search || '');
        setEvent(filters.event || 'all');
        setLevel(filters.level || 'all');
        setDate(filters.date || '');
    }, [filters]);

    const applyFilters = (page = 1) => {
        router.get(route('logs', embeddedQuery), {
            search,
            event,
            level,
            date,
            page,
        }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setEvent('all');
        setLevel('all');
        setDate('');
        router.get(route('logs', embeddedQuery), {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const currentPage = Number(logs?.current_page || 1);
    const lastPage = Number(logs?.last_page || 1);

    const navigatePage = (page) => {
        router.get(route('logs', embeddedQuery), { search, event, level, date, page }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <Page >
            <BlockStack gap="500">
                <Card sectioned>
                    <BlockStack gap="300">
                        <Text variant="headingLg" as="h1">
                            Logs
                        </Text>
                        <Text as="p" tone="subdued">
                            Review activity logs for the current connected Shopify store.
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
                                label="Search"
                                value={search}
                                onChange={setSearch}
                                autoComplete="off"
                                placeholder="Event, message, level, or details"
                            />
                            <Select
                                label="Event"
                                options={[{ label: 'All events', value: 'all' }, ...(filter_options?.events || [])]}
                                value={event}
                                onChange={setEvent}
                            />
                            <Select
                                label="Level"
                                options={filter_options?.levels || []}
                                value={level}
                                onChange={setLevel}
                            />
                            <TextField
                                label="Date"
                                type="date"
                                value={date}
                                onChange={setDate}
                                autoComplete="off"
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

                <Card sectioned>
                    <BlockStack gap="300">
                        <InlineStack align="space-between">
                            <Text variant="headingMd" as="h2">
                                Activity
                            </Text>
                            <Text as="span" tone="subdued">
                                {logs?.total || 0} total
                            </Text>
                        </InlineStack>

                        {logs?.data?.length ? (
                            <IndexTable
                                resourceName={{ singular: 'log', plural: 'logs' }}
                                itemCount={logs.data.length}
                                selectedItemsCount={0}
                                onSelectionChange={() => {}}
                                headings={[
                                    { title: 'Event' },
                                    { title: 'Level' },
                                    { title: 'Message' },
                                    { title: 'Date' },
                                ]}
                            >
                                {logs.data.map((log, index) => (
                                    <IndexTable.Row id={`log-${log.id}`} key={log.id} position={index}>
                                        <IndexTable.Cell>{log.event}</IndexTable.Cell>
                                        <IndexTable.Cell>
                                            <Text as="span" tone={levelTone(log.level)}>{log.level}</Text>
                                        </IndexTable.Cell>
                                        <IndexTable.Cell>{log.message}</IndexTable.Cell>
                                        <IndexTable.Cell>{log.date}</IndexTable.Cell>
                                    </IndexTable.Row>
                                ))}
                            </IndexTable>
                        ) : (
                            <Box paddingBlock="6">
                                <Text as="p" tone="subdued">
                                    No logs found for the current filters.
                                </Text>
                            </Box>
                        )}

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

Logs.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
