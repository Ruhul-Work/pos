<script>
   $(document).ready(function() {
    // Ajax DataTable init
    $(function () {
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        if ($('.AjaxDataTable').length) {
            const table = $('.AjaxDataTable').DataTable({
            dom: '<"dt-top d-flex flex-wrap align-items-center justify-content-between gap-2 mb-12"lfr>t<"dt-bottom d-flex flex-wrap align-items-center justify-content-between mt-12"ip>',
            ordering: true,
            responsive: true,
            stateSave: true,
            order: [[0, 'desc']],
            scrollY: '50vh',
            scrollX: true,
            scrollCollapse: true,
            processing: true,
            serverSide: true,
            serverMethod: 'post',
            ajax: { url: DATATABLE_URL },

            aLengthMenu: [[10, 50, 100, 200, 500,1000, -1],[10, 50, 100, 200, 500,1000, "ALL"]],
            language: {
                search: '',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                next: '<i class="ri-arrow-right-s-line"></i>',
                previous: '<i class="ri-arrow-left-s-line"></i>'
                },
            },

                columnDefs: [
                {
                    targets: 0, // SL checkbox
                    orderable: false,
                    searchable: false,
                    className: 'no-export',
                    render: (data, type, row, meta) => {
                        const sl = String(meta.row + meta.settings._iDisplayStart + 1).padStart(2,'0');
                        return `
                            <div class="form-check style-check d-flex align-items-center">
                            <input class="form-check-input row-check" type="checkbox" data-id="${data}">
                            <label class="form-check-label">${sl}</label>
                            </div>`;
                    }
                },
                {
                    targets: -1, // Action column 
                    orderable: false,   
                    searchable: false, 
                    className: 'no-export text-end',
                    width: '120px'       
                }
            ],

            buttons: [
                { extend: 'copy',  className: 'btn btn-sm btn-light d-none',
                    exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'excel', className: 'btn btn-sm btn-light d-none',
                    exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'print', className: 'btn btn-sm btn-light d-none',
                    exportOptions: { columns: ':visible:not(.no-export)' } }
              
                ],

            drawCallback: function () {
                const $w = $(this.api().table().container());
                $w.find('.dataTables_paginate .pagination')
                .addClass('pagination-sm justify-content-end');
                $w.find('.dataTables_length select')
                .addClass('form-select form-select-sm dt-len');
            },

            initComplete: function (settings) {
                const $wrap = $(settings.nTableWrapper);            
                const $card = $(settings.nTable).closest('.card'); 

                // ✅ এই টেবিলের নিজের ফিল্টারকেই move করুন (গ্লোবাল না)
                $card.find('#tableSearch').empty().append($wrap.find('.dataTables_filter'));

                // Length select bootstrapize
                $wrap.find('.dataTables_length select').addClass('form-select form-select-sm dt-len');

                // ==== External buttons → internal buttons trigger (duplicate free)
                $(document).off('click.dtbtn'); // safety: পুরনো bind খুলে দিন
                $(document).on('click.dtbtn', '.export-copy',  function(e){ e.preventDefault(); table.button('.buttons-copy').trigger();  });
                $(document).on('click.dtbtn', '.export-excel', function(e){ e.preventDefault(); table.button('.buttons-excel').trigger(); });
                $(document).on('click.dtbtn', '.export-print', function(e){ e.preventDefault(); table.button('.buttons-print').trigger(); });

                // ✅ Custom Hide/Show Columns using Swal (ColVis বাদ)
                $(document).on('click.dtbtn', '.export-hide-column', function(e){
                e.preventDefault();

                // কোন কোন কলাম দেখাব/লুকাব — first(0) ও last বাদ চাইলে logic বসান
                let html = '';
                table.columns().every(function(idx){
                    // first: 0 (SL) বাদ, আর দরকার হলে last বাদ দিন
                    if (idx === 0) return;
                    const title = $(this.header()).text().trim() || ('Column ' + (idx+1));
                    const vis   = this.visible();
                    html += `
                    <div class="form-check mb-1 text-start">
                        <input class="form-check-input colvis-toggle" type="checkbox" id="col_${idx}" data-idx="${idx}" ${vis ? 'checked' : ''}>
                        <label class="form-check-label" for="col_${idx}">${title}</label>
                    </div>`;
                });

                    Swal.fire({
                        title: 'Hide/Unhide Columns',
                        html: `<div style="max-height:50vh;overflow:auto;">${html}</div>`,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Apply',
                        cancelButtonText: 'Cancel',
                        customClass: { popup: 'text-start' },
                        preConfirm: () => {
                        document.querySelectorAll('.colvis-toggle').forEach(cb => {
                            const idx = parseInt(cb.dataset.idx, 10);
                            table.column(idx).visible(cb.checked);
                        });
                        }
                    });
                });

                // Refresh
                $(document).on('click.dtbtn', '.export-refresh', function(e){
                e.preventDefault();
                table.ajax.reload(null, false);
                if (window.Swal) Swal.fire({ icon:'success', title:'Reloaded', timer:900, showConfirmButton:false });
                });

                // Master checkbox
                $(document).on('change.dtbtn', '#select-all', function(){
                $('.row-check').prop('checked', this.checked);
                });
            }
        });
        }

        // Tooltips re-init after draw (error-safe)
        $(document).on('draw.dt', function(){
            if (window.bootstrap?.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                try { new bootstrap.Tooltip(el); } catch(_) {}
            });
            }
        });
    });


    // DataTable init (non-ajax)
        if ($('.DataTable').length > 0) {

            table = $('.DataTable').DataTable({
                "bFilter": true,
                "sDom": 'fBtlpi',
                "ordering": true,
                "responsive": true,
                "scrollX": true,
                "scrollY": "60vh",
                "scrollCollapse": true,

                'aLengthMenu': [
                    [10, 50, 100, 200, 500, -1],
                    [10, 50, 100, 200, 500, "ALL"]
                ],
                "language": {
                    search: '',
                    sLengthMenu: '_MENU_',
                    searchPlaceholder: "Search",
                    info: "_START_ - _END_ of _TOTAL_ items",
                    paginate: {
                        next: '<i class="ri-arrow-right-s-line"></i>',
                        previous: '<i class="ri-arrow-left-s-line"></i> '
                    },
                },
                'buttons': [{
                        extend: 'copy',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'csv',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'excel',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'print',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'

                ],
                initComplete: (settings, json) => {
                    $('.dataTables_filter').appendTo('#tableSearch');
                    $('.dataTables_filter').appendTo('.search-input');

                    $(document).on('click', '.export-excel', function() {
                        $('.dt-buttons .buttons-excel').click();
                    });

                    $(document).on('click', '.export-print', function() {
                        $('.dt-buttons .buttons-print').click();
                    });

                    $(document).on('click', '.export-copy', function() {
                        $('.dt-buttons .buttons-copy').click();
                        Swal.fire({
                            title: "Success",
                            text: "Successfully copied",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500
                        });
                    });

                    $(document).on('click', '.export-refresh', function() {
                        //$('.DataTable').DataTable().ajax.reload();
                        $('.DataTable').DataTable().draw();
                        Swal.fire({
                            title: "Success",
                            text: "Successfully Reloaded",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500
                        });
                    });


                    // Custom function to toggle column visibility
                    function toggleColumn(index) {
                        table.column(index).visible(!table.column(index).visible());
                    }

                    // Event listener for column hide/unhide button
                    $(document).on('click', '.export-hide-column', function() {
                        var columnCheckboxes = '';
                        table.columns().every(function() {
                            var column = this;
                            var columnTitle = $(column.header()).text().trim();

                            var columnIndex = column.index();
                            columnCheckboxes +=
                                `<div style="text-align:left;"><input type="checkbox" id="chk_${columnIndex}" class="column-checkbox" value="${columnIndex}" ${column.visible() ? 'checked' : ''}><label for="chk_${columnIndex}">${columnTitle}</label></div>`;
                        });

                        Swal.fire({
                            title: 'Hide/Unhide Columns',
                            html: columnCheckboxes,
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Apply',
                            cancelButtonText: 'Cancel',
                            preConfirm: () => {
                                $('.column-checkbox').each(function() {
                                    var columnIndex = $(this).val();
                                    var isChecked = $(this).prop(
                                        'checked');
                                    if (isChecked !== table.column(
                                            columnIndex).visible()) {
                                        toggleColumn(columnIndex);
                                    }
                                });
                            }
                        });
                    });



                },
            });
        }
    });



</script>

