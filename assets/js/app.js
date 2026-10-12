// Interaksi kecil di sisi browser. Semua aturan tetap divalidasi ulang di server.
document.addEventListener('DOMContentLoaded', function () {
    // Pesan berhasil hilang otomatis setelah 5 detik
    document.querySelectorAll('[data-auto-dismiss]').forEach(function (alertEl) {
        setTimeout(function () {
            bootstrap.Alert.getOrCreateInstance(alertEl).close();
        }, 5000);
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
    var customerSelect = document.getElementById('customer_id');
    var reservationSelect = document.getElementById('reservation_id');
    var orderDate = document.getElementById('order_date');
    var dateHint = document.getElementById('order-date-hint');

    // Reservasi hanya menampilkan milik pelanggan yang dipilih
    var filterReservations = function () {
        var customerId = customerSelect.value;
        Array.prototype.forEach.call(reservationSelect.options, function (option) {
            if (!option.dataset.customer) {
                return;
            }
            var visible = customerId !== '' && option.dataset.customer === customerId;
            option.hidden = !visible;
            option.disabled = !visible;
        });
        var selected = reservationSelect.selectedOptions[0];
        if (selected && selected.disabled) {
            reservationSelect.value = '';
        }
        syncOrderDate();
    };

    // Jika reservasi dipilih, tanggal pesanan mengikuti tanggal reservasi
    var syncOrderDate = function () {
        var selected = reservationSelect.selectedOptions[0];
        if (selected && selected.dataset.date) {
            orderDate.value = selected.dataset.date;
            orderDate.readOnly = true;
            dateHint.textContent = 'Mengikuti tanggal reservasi.';
        } else {
            orderDate.readOnly = false;
            dateHint.textContent = 'Tidak boleh melebihi hari ini.';
        }
    };

    customerSelect.addEventListener('change', filterReservations);
    reservationSelect.addEventListener('change', function () {
        var selected = reservationSelect.selectedOptions[0];
        if (selected && selected.dataset.customer) {
            customerSelect.value = selected.dataset.customer;
        }
        syncOrderDate();
    });
    filterReservations();

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
