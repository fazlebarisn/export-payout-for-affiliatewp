jQuery(document).ready(function($) {
    // Check if we are on the payouts tab. Look for the payouts table or a specific class.
    // AffiliateWP usually uses .affwp-table-payouts or similar on the payouts tab.
    // Let's check for any .affwp-table in the payouts tab, or if there's a heading "Referral Payouts".
    
    let isPayoutsTab = false;
    let targetElement = null;

    // Check by heading
    $('h2, h3, h4').each(function() {
        if ($(this).text().trim() === 'Referral Payouts') {
            isPayoutsTab = true;
            targetElement = $(this);
        }
    });

    // Check by URL parameter or element ID if heading not found
    if (!isPayoutsTab) {
        if (window.location.search.indexOf('tab=payouts') > -1 || $('#affwp-affiliate-dashboard-payouts').length > 0) {
            isPayoutsTab = true;
            // Target the table to insert before
            targetElement = $('.affwp-table').first();
        }
    }

    if (isPayoutsTab && targetElement && targetElement.length > 0) {
        // Insert the button
        const exportBtn = $('<button id="epfaw-open-modal-btn">Export Payouts</button>');
        
        if (targetElement.is('h2, h3, h4')) {
            targetElement.after(exportBtn);
        } else {
            targetElement.before(exportBtn);
        }

        // Open modal
        exportBtn.on('click', function(e) {
            e.preventDefault();
            $('#epfaw-modal-overlay').fadeIn(200);
            $('#epfaw-modal-overlay').css('display', 'flex'); // Ensure flex display for centering
        });
    }

    // Close modal
    $('#epfaw-close-modal, #epfaw-modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $('#epfaw-modal-overlay').fadeOut(200);
        }
    });

    // Handle duration change to show/hide custom dates
    $('#epfaw-duration').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#epfaw-custom-dates').slideDown(200);
        } else {
            $('#epfaw-custom-dates').slideUp(200);
        }
    });

    // Handle form submission
    $('#epfaw-export-form').on('submit', function(e) {
        e.preventDefault();
        
        const duration = $('#epfaw-duration').val();
        const dateFrom = $('#epfaw-date-from').val();
        const dateTo = $('#epfaw-date-to').val();
        const fileType = $('#epfaw-file-type').val();

        if (duration === 'custom' && (!dateFrom || !dateTo)) {
            alert('Please select both start and end dates.');
            return;
        }

        const submitBtn = $('#epfaw-export-btn');
        const loadingMsg = $('#epfaw-loading');

        submitBtn.prop('disabled', true);
        loadingMsg.show();

        // AJAX request to get payouts data
        $.ajax({
            url: epfaw_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'epfaw_get_payouts',
                nonce: epfaw_ajax.nonce,
                duration: duration,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    if (data.length === 0) {
                        alert('No payouts found for the selected duration.');
                    } else {
                        generateExport(data, fileType);
                        $('#epfaw-modal-overlay').fadeOut(200);
                    }
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while fetching the data.');
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                loadingMsg.hide();
            }
        });
    });

    function generateExport(data, type) {
        const fileName = 'payouts_export_' + new Date().getTime();

        if (type === 'csv') {
            exportCSV(data, fileName + '.csv');
        } else if (type === 'excel') {
            exportExcel(data, fileName + '.xlsx');
        } else if (type === 'pdf') {
            exportPDF(data, fileName + '.pdf');
        }
    }

    function exportCSV(data, fileName) {
        const headers = Object.keys(data[0]);
        let csvContent = headers.join(',') + '\n';

        data.forEach(function(row) {
            let rowArray = headers.map(header => {
                let cell = row[header] === null ? '' : row[header].toString();
                // Escape quotes
                cell = cell.replace(/"/g, '""');
                // Enclose in quotes if contains comma, newline, or quote
                if (cell.search(/("|,|\n)/g) >= 0) {
                    cell = `"${cell}"`;
                }
                return cell;
            });
            csvContent += rowArray.join(',') + '\n';
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        downloadBlob(blob, fileName);
    }

    function exportExcel(data, fileName) {
        if (typeof XLSX === 'undefined') {
            alert('Excel library failed to load.');
            return;
        }

        const worksheet = XLSX.utils.json_to_sheet(data);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Payouts");
        
        XLSX.writeFile(workbook, fileName);
    }

    function exportPDF(data, fileName) {
        if (typeof window.jspdf === 'undefined') {
            alert('PDF library failed to load.');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const headers = Object.keys(data[0]);
        const rows = data.map(obj => headers.map(h => obj[h]));

        doc.text("Affiliate Payouts", 14, 15);
        
        doc.autoTable({
            head: [headers],
            body: rows,
            startY: 20,
            theme: 'striped',
            headStyles: { fillColor: [0, 115, 170] }
        });

        doc.save(fileName);
    }

    function downloadBlob(blob, fileName) {
        const link = document.createElement("a");
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", fileName);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }
});
