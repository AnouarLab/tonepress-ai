// Bulk generation JavaScript handlers
(function ($) {
    'use strict';

    $(document).ready(function () {

        // Handle CSV upload
        $('#ace-bulk-upload-form').on('submit', function (e) {
            e.preventDefault();

            const fileInput = $('#ace-csv-file')[0];
            if (!fileInput.files.length) {
                ace_showToast('Error', 'Please select a CSV file', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'ace_bulk_upload');
            formData.append('nonce', aceAdmin.nonce);
            formData.append('csv_file', fileInput.files[0]);

            $('#ace-bulk-upload-btn').prop('disabled', true).text('Uploading...');

            $.ajax({
                url: aceAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#ace-bulk-upload-btn').prop('disabled', false).text('Upload and Start Generation');

                    if (response.success) {
                        ace_showToast('Success!', response.data.message, 'success');
                        $('#ace-csv-file').val('');
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    } else {
                        ace_showToast('Error', response.data.message, 'error');
                    }
                },
                error: function () {
                    $('#ace-bulk-upload-btn').prop('disabled', false).text('Upload and Start Generation');
                    ace_showToast('Error', 'Upload failed. Please try again.', 'error');
                }
            });
        });

        // Pause queue
        $(document).on('click', '.ace-pause-queue', function () {
            const queue_id = $(this).data('queue');

            $.ajax({
                url: aceAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ace_bulk_pause',
                    nonce: aceAdmin.nonce,
                    queue_id: queue_id
                },
                success: function (response) {
                    if (response.success) {
                        ace_showToast('Success', response.data.message, 'success');
                        location.reload();
                    } else {
                        ace_showToast('Error', response.data.message, 'error');
                    }
                }
            });
        });

        // Resume queue
        $(document).on('click', '.ace-resume-queue', function () {
            const queue_id = $(this).data('queue');

            $.ajax({
                url: aceAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ace_bulk_resume',
                    nonce: aceAdmin.nonce,
                    queue_id: queue_id
                },
                success: function (response) {
                    if (response.success) {
                        ace_showToast('Success', response.data.message, 'success');
                        location.reload();
                    } else {
                        ace_showToast('Error', response.data.message, 'error');
                    }
                }
            });
        });

        // Delete queue
        $(document).on('click', '.ace-delete-queue', function () {
            if (!confirm('Are you sure you want to delete this queue?')) {
                return;
            }

            const queue_id = $(this).data('queue');

            $.ajax({
                url: aceAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ace_bulk_delete',
                    nonce: aceAdmin.nonce,
                    queue_id: queue_id
                },
                success: function (response) {
                    if (response.success) {
                        ace_showToast('Success', response.data.message, 'success');
                        location.reload();
                    } else {
                        ace_showToast('Error', response.data.message, 'error');
                    }
                }
            });
        });

        // Helper function (reuse from main admin.js)
        function ace_showToast(title, message, type) {
            if (typeof showToast === 'function') {
                showToast(title, message, type);
            }
        }
    });

})(jQuery);
