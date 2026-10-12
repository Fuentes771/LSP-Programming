// Interaksi kecil di sisi browser. Semua aturan tetap divalidasi ulang di server.
document.addEventListener('DOMContentLoaded', function () {
    // Pesan berhasil hilang otomatis setelah 5 detik
    document.querySelectorAll('[data-auto-dismiss]').forEach(function (alertEl) {
        setTimeout(function () {
            bootstrap.Alert.getOrCreateInstance(alertEl).close();
        }, 5000);
    });

    // Pemilih pelanggan: tampilkan pilihan pelanggan terdaftar atau isian pelanggan baru
    var currentCustomerMode = function (picker) {
        var checked = picker.querySelector('input[name="customer_mode"]:checked');
        return checked ? checked.value : 'existing';
    };
    document.querySelectorAll('.customer-picker').forEach(function (picker) {
        var applyMode = function () {
            var mode = currentCustomerMode(picker);
            picker.querySelectorAll('[data-customer-section]').forEach(function (section) {
                var active = section.dataset.customerSection === mode;
                section.hidden = !active;
                section.querySelectorAll('[data-required]').forEach(function (field) {
                    field.required = active;
                });
            });
        };
        picker.querySelectorAll('input[name="customer_mode"]').forEach(function (radio) {
            radio.addEventListener('change', applyMode);
        });
        applyMode();
    });

    // Form reservasi: nonaktifkan meja yang kapasitasnya kurang dari jumlah tamu
    var guestInput = document.getElementById('guest_count');
    var tableSelect = document.getElementById('table_id');
    if (guestInput && tableSelect) {
        var filterTables = function () {
            var guests = parseInt(guestInput.value, 10) || 0;
            Array.prototype.forEach.call(tableSelect.options, function (option) {
                if (!option.dataset.capacity) {
                    return;
                }
                var tooSmall = guests > parseInt(option.dataset.capacity, 10);
                option.disabled = tooSmall;
                option.textContent = option.dataset.label + (tooSmall ? ' — kapasitas kurang' : '');
            });
            if (tableSelect.selectedOptions.length && tableSelect.selectedOptions[0].disabled) {
                tableSelect.value = '';
            }
        };
        guestInput.addEventListener('input', filterTables);
        filterTables();
    }

    // Form pesanan
    var orderForm = document.getElementById('order-form');
    if (!orderForm) {
        return;
    }

    // Jenis pesanan: "Dari reservasi" atau "Tanpa reservasi". Bagian yang tidak dipakai dinonaktifkan
    // (fieldset disabled) agar isiannya tidak divalidasi dan tidak ikut terkirim.
    var applyOrderType = function () {
        var checked = orderForm.querySelector('input[name="order_type"]:checked');
        var type = checked ? checked.value : 'walkin';
        orderForm.querySelectorAll('[data-order-section]').forEach(function (section) {
            var active = section.dataset.orderSection === type;
            section.hidden = !active;
            section.disabled = !active;
        });
    };
    orderForm.querySelectorAll('input[name="order_type"]').forEach(function (radio) {
        radio.addEventListener('change', applyOrderType);
    });
    applyOrderType();

    // Memilih reservasi langsung menampilkan pelanggan, meja, jadwal, dan jumlah tamu
    var reservationSelect = document.getElementById('reservation_id');
    var reservationInfo = document.getElementById('reservation-info');
    if (reservationSelect && reservationInfo) {
        var showReservationInfo = function () {
            var option = reservationSelect.selectedOptions[0];
            if (!option || !option.value) {
                reservationInfo.hidden = true;
                return;
            }
            var values = {
                customer: option.dataset.customer + ' (' + option.dataset.phone + ')',
                table: option.dataset.table,
                schedule: option.dataset.schedule,
                guests: option.dataset.guests + ' orang'
            };
            Object.keys(values).forEach(function (field) {
                reservationInfo.querySelector('[data-info="' + field + '"]').textContent = values[field];
            });
            reservationInfo.hidden = false;
        };
        reservationSelect.addEventListener('change', showReservationInfo);
        showReservationInfo();
    }

    // Pratinjau subtotal dan total
    var formatRupiah = function (value) {
        return 'Rp ' + Math.round(value).toLocaleString('id-ID');
    };
    var recalculate = function () {
        var total = 0;
        var itemCount = 0;
        orderForm.querySelectorAll('.menu-row').forEach(function (row) {
            var qty = parseInt(row.querySelector('.qty-input').value, 10);
            qty = isNaN(qty) || qty < 0 ? 0 : qty;
            var subtotal = qty * parseFloat(row.dataset.price);
            row.querySelector('.row-subtotal').textContent = qty > 0 ? formatRupiah(subtotal) : '-';
            row.classList.toggle('selected', qty > 0);
            total += subtotal;
            itemCount += qty;
        });
        document.getElementById('order-total').textContent = formatRupiah(total);
        document.getElementById('order-items').textContent = itemCount;
    };

    orderForm.querySelectorAll('.qty-group').forEach(function (group) {
        var qtyInput = group.querySelector('.qty-input');
        group.querySelector('.qty-minus').addEventListener('click', function () {
            qtyInput.value = Math.max(0, (parseInt(qtyInput.value, 10) || 0) - 1);
            recalculate();
        });
        group.querySelector('.qty-plus').addEventListener('click', function () {
            qtyInput.value = Math.min(parseInt(qtyInput.max, 10), (parseInt(qtyInput.value, 10) || 0) + 1);
            recalculate();
        });
        qtyInput.addEventListener('input', recalculate);
    });
    recalculate();
});
