$(function () {
    const moduleTable = $('#moduleTable');
    const moduleForm = $('#moduleForm');
    const clientSwitcher = $('#clientSwitcher');
    const activityFeed = $('#activityFeed');
    const financialTable = $('#financialTable');
    const reportModuleSelect = $('#reportModule');

    if (moduleTable.length && moduleForm.length) {
        initModulePage(moduleForm, moduleTable);
    }

    if (clientSwitcher.length) {
        hydrateClientSwitcher(clientSwitcher);
    }

    if (activityFeed && activityFeed.length) {
        loadRecentActivity(activityFeed);
    }

    if (financialTable && financialTable.length) {
        loadFinancialSummary(financialTable);
    }

    if (reportModuleSelect.length) {
        initReports(reportModuleSelect);
    }
});

const relationCache = {};

function initModulePage($form, $table) {
    const moduleKey = $table.data('module');
    const columnList = ($table.data('columns') || '').split(',').filter(Boolean);
    const fileFields = ($table.data('file-fields') || '').split(',').filter(Boolean);

    loadRelations($form);
    loadRecords($table, columnList, fileFields);

    $form.on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save');
        formData.append('module', moduleKey);

        $.ajax({
            url: '/api.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).done(function (response) {
            if (response.generated_password) {
                alert('User password: ' + response.generated_password);
            }
            $form[0].reset();
            $form.find('#recordId').val('');
            loadRecords($table, columnList, fileFields);
        }).fail(function (xhr) {
            alert(parseError(xhr));
        });
    });

    $('#resetFormBtn').on('click', function () {
        $form[0].reset();
        $form.find('#recordId').val('');
    });

    $table.on('click', '.edit-btn', function () {
        const record = $(this).data('record');
        populateForm($form, record, fileFields);
    });

    $table.on('click', '.delete-btn', function () {
        if (!confirm('Delete this record?')) {
            return;
        }
        const recordId = $(this).data('id');
        $.post('/api.php', {action: 'delete', module: moduleKey, id: recordId})
            .done(function () {
                loadRecords($table, columnList, fileFields);
            })
            .fail(function (xhr) {
                alert(parseError(xhr));
            });
    });
}

function loadRelations($form) {
    $form.find('.relation-field').each(function () {
        const $select = $(this);
        const source = $select.data('source');
        fetchRelationOptions(source, function (options) {
            renderRelationOptions($select, options);
        });
    });
}

function fetchRelationOptions(source, callback) {
    if (relationCache[source]) {
        callback(relationCache[source]);
        return;
    }
    $.getJSON('/api.php', {action: 'relations', source: source})
        .done(function (response) {
            relationCache[source] = response.options || [];
            callback(relationCache[source]);
        })
        .fail(function (xhr) {
            alert(parseError(xhr));
        });
}

function renderRelationOptions($select, options) {
    const selectedValue = $select.data('selected') || '';
    $select.empty().append($('<option>').val('').text('Select'));
    (options || []).forEach(function (option) {
        const opt = $('<option>').val(option.id).text(option.label);
        $select.append(opt);
    });
    if (selectedValue) {
        $select.val(selectedValue);
    }
    $select.removeData('selected');
}

function loadRecords($table, columns, fileFields) {
    const moduleKey = $table.data('module');
    $.getJSON('/api.php', {action: 'list', module: moduleKey})
        .done(function (response) {
            renderTable($table, response.records || [], columns, fileFields);
        })
        .fail(function (xhr) {
            alert(parseError(xhr));
        });
}

function renderTable($table, records, columns, fileFields) {
    const $tbody = $table.find('tbody');
    $tbody.empty();
    if (!records.length) {
        $tbody.append('<tr><td colspan="' + (columns.length + 2) + '">No records</td></tr>');
        return;
    }
    records.forEach(function (record) {
        const $row = $('<tr>');
        const $actions = $('<td class="text-nowrap">');
        const editBtn = $('<button type="button" class="btn btn-sm btn-outline-primary me-2 edit-btn">Edit</button>');
        const deleteBtn = $('<button type="button" class="btn btn-sm btn-outline-danger delete-btn">Delete</button>');
        editBtn.data('record', record);
        deleteBtn.data('id', record.id);
        $actions.append(editBtn, deleteBtn);
        $row.append($actions);

        columns.forEach(function (column) {
            const value = record[column];
            const display = formatCellValue(value, column, fileFields);
            $row.append($('<td>').html(display));
        });

        $row.append($('<td>').text(record.created_at || ''));
        $tbody.append($row);
    });
}

function formatCellValue(value, column, fileFields) {
    if (!value && value !== 0) {
        return '';
    }
    if (fileFields.includes(column) && typeof value === 'string') {
        return '<a href="' + value + '" target="_blank" rel="noopener">View</a>';
    }
    if (/date/i.test(column) && typeof value === 'string') {
        return value;
    }
    return $('<div>').text(value).html();
}

function populateForm($form, record, fileFields) {
    $form[0].reset();
    $form.find('#recordId').val(record.id);

    Object.keys(record).forEach(function (key) {
        if (key === 'id' || key === 'created_at') {
            return;
        }
        const $field = $form.find('[name="' + key + '"]');
        if (!$field.length) {
            return;
        }
        if ($field.hasClass('relation-field')) {
            $field.data('selected', record[key]);
            const source = $field.data('source');
            fetchRelationOptions(source, function (options) {
                renderRelationOptions($field, options);
            });
            return;
        }
        if ($field.attr('type') === 'file') {
            const $hidden = $form.find('#existing_' + key);
            if ($hidden.length) {
                $hidden.val(record[key] || '');
            }
            return;
        }
        $field.val(record[key]);
    });

    fileFields.forEach(function (field) {
        const $hidden = $form.find('#existing_' + field);
        if ($hidden.length) {
            $hidden.val(record[field] || '');
        }
    });
}

function hydrateClientSwitcher($switcher) {
    $.getJSON('/api.php', {action: 'list', module: 'clients'})
        .done(function (response) {
            const activeClient = $switcher.data('active-client') || '';
            (response.records || []).forEach(function (record) {
                const option = $('<option>').val(record.id).text(record.name);
                $switcher.append(option);
            });
            if (activeClient) {
                $switcher.val(activeClient);
            }
        })
        .fail(function (xhr) {
            alert(parseError(xhr));
        });

    $switcher.on('change', function () {
        $.post('/api.php', {action: 'setWorkspace', client_id: $(this).val()})
            .done(function () {
                window.location.reload();
            })
            .fail(function (xhr) {
                alert(parseError(xhr));
            });
    });
}

function loadRecentActivity($container) {
    $.getJSON('/api.php', {action: 'activity'})
        .done(function (response) {
            const records = response.records || [];
            $container.empty();
            if (!records.length) {
                $container.append('<tr><td colspan="3">No activity yet.</td></tr>');
                return;
            }
            records.forEach(function (record) {
                const row = $('<tr>');
                row.append($('<td>').text(titleCase(record.module || '')));
                row.append($('<td>').text(record.description || ''));
                row.append($('<td>').text(record.created_at || ''));
                $container.append(row);
            });
        })
        .fail(function (xhr) {
            alert(parseError(xhr));
        });
}

function loadFinancialSummary($table) {
    $.getJSON('/api.php', {action: 'financialSummary'})
        .done(function (response) {
            const budget = Number(response.budget_total || 0);
            const actual = Number(response.actual_total || 0);
            $('#budgetTotal').text(formatCurrency(budget));
            $('#actualTotal').text(formatCurrency(actual));
            $('#varianceTotal').text(formatCurrency(budget - actual));

            const $tbody = $table.find('tbody');
            $tbody.empty();
            (response.projects || []).forEach(function (project) {
                const row = $('<tr>');
                row.append($('<td>').text(project.name));
                row.append($('<td>').text(formatCurrency(project.budget)));
                $tbody.append(row);
            });
        })
        .fail(function (xhr) {
            alert(parseError(xhr));
        });
}

function initReports($select) {
    const $table = $('#reportTable');
    loadReportData($select.val(), $table);

    $select.on('change', function () {
        loadReportData($(this).val(), $table);
    });

    $('.report-export').on('click', function () {
        const format = $(this).data('format');
        const module = $select.val();
        window.location.href = '/api.php?action=export&module=' + encodeURIComponent(module) + '&format=' + format;
    });
}

function loadReportData(moduleKey, $table) {
    $.getJSON('/api.php', {action: 'list', module: moduleKey})
        .done(function (response) {
            const records = response.records || [];
            renderReportTable($table, records);
        })
        .fail(function (xhr) {
            alert(parseError(xhr));
        });
}

function renderReportTable($table, records) {
    const $thead = $table.find('thead');
    const $tbody = $table.find('tbody');
    $thead.empty();
    $tbody.empty();

    if (!records.length) {
        $tbody.append('<tr><td colspan="1">No data available</td></tr>');
        return;
    }

    const columns = Object.keys(records[0]);
    const headerRow = $('<tr>');
    columns.forEach(function (column) {
        headerRow.append($('<th>').text(titleCase(column)));
    });
    $thead.append(headerRow);

    records.forEach(function (record) {
        const row = $('<tr>');
        columns.forEach(function (column) {
            row.append($('<td>').text(record[column]));
        });
        $tbody.append(row);
    });
}

function parseError(xhr) {
    try {
        const payload = JSON.parse(xhr.responseText);
        if (payload.error) {
            return payload.error;
        }
    } catch (e) {
        /* ignore */
    }
    return 'An unexpected error occurred.';
}

function titleCase(text) {
    return (text || '').toString().replace(/_/g, ' ').replace(/\w\S*/g, function (str) {
        return str.charAt(0).toUpperCase() + str.substr(1).toLowerCase();
    });
}

function formatCurrency(amount) {
    const value = Number(amount) || 0;
    return 'INR ' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
