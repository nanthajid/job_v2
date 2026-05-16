$(function () {

  /* ====================================================
     Mock data — replace with AJAX/PHP when DB is ready
     ==================================================== */
  var totalRegistered = parseInt($('#total-count').data('value')) || 0;

  var districtData = {
    labels: phpDistrictData.map(function(d) { return d.label; }),
    counts: phpDistrictData.map(function(d) { return d.count; }),
    colors: phpDistrictData.map(function(d) { return d.color; })
  };

  var totalCheckin = parseInt($('#checkin-count').data('value')) || 0;

  var checkinDistrictData = {
    labels: phpCheckinDistrictData.map(function(d) { return d.label; }),
    counts: phpCheckinDistrictData.map(function(d) { return d.count; }),
    colors: phpCheckinDistrictData.map(function(d) { return d.color; })
  };

  var reasonData = {
    labels: phpReasonData.map(function(d) { return d.label; }),
    counts: phpReasonData.map(function(d) { return d.count; }),
    colors: phpReasonData.map(function(d) { return d.color; })
  };

  /* ====================================================
     Animate counters
     ==================================================== */
  function animateCounter($el, target, suffix) {
    suffix = suffix || '';
    $({ val: 0 }).animate({ val: target }, {
      duration: 1200,
      easing: 'swing',
      step: function () { $el.text(Math.ceil(this.val).toLocaleString('th-TH') + suffix); },
      complete: function () { $el.text(target.toLocaleString('th-TH') + suffix); }
    });
  }

  animateCounter($('#total-count'), totalRegistered, ' ครั้ง');
  animateCounter($('#checkin-count'), totalCheckin, ' ครั้ง');

  districtData.counts.forEach(function (val, i) {
    animateCounter($('#district-count-' + i), val);
  });

  checkinDistrictData.counts.forEach(function (val, i) {
    animateCounter($('#checkin-district-' + i), val);
  });


  /* ====================================================
     Bar chart — แยกตามเขต
     ==================================================== */
  var ctxBar = document.getElementById('chartDistrict').getContext('2d');
  new Chart(ctxBar, {
    type: 'bar',
    data: {
      labels: districtData.labels,
      datasets: [{
        label: 'จำนวนผู้ลงทะเบียน (ราย)',
        data: districtData.counts,
        backgroundColor: districtData.colors,
        borderRadius: 6,
        barThickness: 40
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      legend: { display: false },
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true,
            callback: function (v) { return v.toLocaleString('th-TH'); }
          },
          gridLines: { color: 'rgba(0,0,0,.05)' }
        }],
        xAxes: [{
          gridLines: { display: false }
        }]
      },
      tooltips: {
        callbacks: {
          label: function (item) {
            return ' ' + parseInt(item.value).toLocaleString('th-TH') + ' ราย';
          }
        }
      }
    }
  });

  /* ====================================================
     Grouped bar chart — รายงานตัวแยกตามเขต
     ==================================================== */
  var ctxCheckin = document.getElementById('chartCheckinDistrict').getContext('2d');
  new Chart(ctxCheckin, {
    type: 'bar',
    data: {
      labels: checkinDistrictData.labels,
      datasets: [
        {
          label: 'ผู้ลงทะเบียน (ราย)',
          data: districtData.counts,
          backgroundColor: 'rgba(52,144,220,.35)',
          borderColor: 'rgba(52,144,220,.8)',
          borderWidth: 2
        },
        {
          label: 'ผู้มารายงานตัว (ราย)',
          data: checkinDistrictData.counts,
          backgroundColor: checkinDistrictData.colors,
          borderColor: checkinDistrictData.colors.map(function (c) { return c.replace('.85', '1'); }),
          borderWidth: 2
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      legend: {
        position: 'top',
        labels: { fontSize: 12, padding: 16 }
      },
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true,
            callback: function (v) { return v.toLocaleString('th-TH'); }
          },
          gridLines: { color: 'rgba(0,0,0,.05)' }
        }],
        xAxes: [{
          gridLines: { display: false }
        }]
      },
      tooltips: {
        mode: 'index',
        intersect: false,
        callbacks: {
          label: function (item, data) {
            return ' ' + data.datasets[item.datasetIndex].label + ': ' +
              parseInt(item.value).toLocaleString('th-TH') + ' ราย';
          }
        }
      }
    }
  });

  /* ====================================================
     Doughnut chart — แยกตามสาเหตุ
     ==================================================== */
  var quitData = {
    labels: phpQuitData.map(function(d) { return d.label; }),
    counts: phpQuitData.map(function(d) { return d.count; }),
    colors: phpQuitData.map(function(d) { return d.color; })
  };

  var ctxDoughnut = document.getElementById('chartReason').getContext('2d');
  new Chart(ctxDoughnut, {
    type: 'doughnut',
    data: {
      labels: quitData.labels,
      datasets: [{
        data: quitData.counts,
        backgroundColor: quitData.colors,
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutoutPercentage: 60,
      legend: {
        position: 'bottom',
        labels: { fontSize: 12, padding: 12 }
      },
      tooltips: {
        callbacks: {
          label: function (item, data) {
            var val = data.datasets[0].data[item.index];
            var total = data.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);
            var pct = ((val / total) * 100).toFixed(1);
            return ' ' + data.labels[item.index] + ': ' + val.toLocaleString('th-TH') + ' ราย (' + pct + '%)';
          }
        }
      }
    }
  });

  /* ====================================================
     Build progress bars for reasons
     ==================================================== */
  var $reasonList = $('#reason-progress-list');

  reasonData.labels.forEach(function (label, i) {
    var cnt    = reasonData.counts[i];
    var pct    = totalCheckin > 0 ? ((cnt / totalCheckin) * 100).toFixed(1) : 0;
    var color  = reasonData.colors[i];
    $reasonList.append(
      '<div class="reason-row d-flex align-items-center">' +
        '<div class="reason-icon mr-3" style="background-color:' + color + ';">' +
          '<i class="fas fa-circle" style="font-size:8px;"></i>' +
        '</div>' +
        '<div class="flex-grow-1">' +
          '<div class="progress-label">' +
            '<span>' + label + '</span>' +
            '<strong id="reason-count-' + i + '">0</strong>' +
          '</div>' +
          '<div class="progress" style="height:6px;">' +
            '<div class="progress-bar" style="width:' + pct + '%;background-color:' + color + ';" ' +
              'data-toggle="tooltip" title="' + pct + '%"></div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
    animateCounter($('#reason-count-' + i), cnt);
  });

  $reasonList.append(
    '<div class="d-flex align-items-center justify-content-between px-2 pt-2 border-top mt-1">' +
      '<span class="font-weight-bold text-dark" style="font-size:.9rem;">' +
        '<i class="fas fa-clipboard-check mr-1 text-danger"></i>รวมทั้งหมด' +
      '</span>' +
      '<strong id="reason-total" class="text-danger" style="font-size:1.2rem;">0</strong>' +
    '</div>'
  );
  animateCounter($('#reason-total'), totalCheckin);

  $('[data-toggle="tooltip"]').tooltip();

  /* ====================================================
     Line chart — ผู้ลงทะเบียนรายวัน
     ==================================================== */
  var dailyChart = null;

  function loadDailyChart(dateFrom, dateTo) {
    $.getJSON('api/daily_register.php', { date_from: dateFrom, date_to: dateTo }, function (res) {
      if (!res.success) { return; }

      var labels = res.data.map(function (d) {
        var p = d.day.split('-');
        return p[2] + '/' + p[1];
      });
      var counts = res.data.map(function (d) { return d.cnt; });

      if (dailyChart) {
        dailyChart.data.labels = labels;
        dailyChart.data.datasets[0].data = counts;
        dailyChart.update();
        return;
      }

      var ctx = document.getElementById('chartDailyReg').getContext('2d');
      dailyChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'จำนวนผู้มาขึ้นทะเบียนว่างงาน (ราย)',
            data: counts,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#28a745',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            lineTension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          legend: { display: false },
          scales: {
            xAxes: [{
              gridLines: { display: false },
              ticks: { autoSkip: true, maxTicksLimit: 20, fontSize: 11 }
            }],
            yAxes: [{
              ticks: {
                beginAtZero: true,
                precision: 0,
                callback: function (v) { return v.toLocaleString('th-TH'); }
              },
              gridLines: { color: 'rgba(0,0,0,.05)' }
            }]
          },
          tooltips: {
            mode: 'index',
            intersect: false,
            callbacks: {
              title: function (items) { return 'วันที่ ' + items[0].xLabel; },
              label: function (item) {
                return ' ผู้มาขึ้นทะเบียน: ' + parseInt(item.value).toLocaleString('th-TH') + ' ราย';
              }
            }
          }
        }
      });
    });
  }

  function fmtDate(d) {
    return d.getFullYear() + '-' +
      String(d.getMonth() + 1).padStart(2, '0') + '-' +
      String(d.getDate()).padStart(2, '0');
  }

  var today        = new Date();
  var firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

  flatpickr('#filterDateFrom', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    defaultDate: fmtDate(firstOfMonth)
  });
  flatpickr('#filterDateTo', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    defaultDate: fmtDate(today)
  });

  loadDailyChart(fmtDate(firstOfMonth), fmtDate(today));

  $('#btnApplyFilter').on('click', function () {
    var f = $('#filterDateFrom').val();
    var t = $('#filterDateTo').val();
    if (!f || !t) { return; }
    if (f > t) { alert('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด'); return; }
    loadDailyChart(f, t);
  });

  /* ====================================================
     Line chart — ผู้รายงานตัวรายวัน
     ==================================================== */
  var checkinDailyChart = null;

  function loadCheckinDailyChart(dateFrom, dateTo) {
    $.getJSON('api/daily_checkin.php', { date_from: dateFrom, date_to: dateTo }, function (res) {
      if (!res.success) { return; }

      var labels = res.data.map(function (d) {
        var p = d.day.split('-');
        return p[2] + '/' + p[1];
      });
      var counts = res.data.map(function (d) { return d.cnt; });

      if (checkinDailyChart) {
        checkinDailyChart.data.labels = labels;
        checkinDailyChart.data.datasets[0].data = counts;
        checkinDailyChart.update();
        return;
      }

      var ctx = document.getElementById('chartDailyCheckin').getContext('2d');
      checkinDailyChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'จำนวนผู้มารายงานตัวว่างงาน (ราย)',
            data: counts,
            borderColor: '#20c997',
            backgroundColor: 'rgba(32,201,151,.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#20c997',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            lineTension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          legend: { display: false },
          scales: {
            xAxes: [{
              gridLines: { display: false },
              ticks: { autoSkip: true, maxTicksLimit: 20, fontSize: 11 }
            }],
            yAxes: [{
              ticks: {
                beginAtZero: true,
                precision: 0,
                callback: function (v) { return v.toLocaleString('th-TH'); }
              },
              gridLines: { color: 'rgba(0,0,0,.05)' }
            }]
          },
          tooltips: {
            mode: 'index',
            intersect: false,
            callbacks: {
              title: function (items) { return 'วันที่ ' + items[0].xLabel; },
              label: function (item) {
                return ' ผู้มารายงานตัว: ' + parseInt(item.value).toLocaleString('th-TH') + ' ราย';
              }
            }
          }
        }
      });
    });
  }

  flatpickr('#ciFilterDateFrom', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    defaultDate: fmtDate(firstOfMonth)
  });
  flatpickr('#ciFilterDateTo', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    defaultDate: fmtDate(today)
  });

  loadCheckinDailyChart(fmtDate(firstOfMonth), fmtDate(today));

  $('#btnApplyCheckin').on('click', function () {
    var f = $('#ciFilterDateFrom').val();
    var t = $('#ciFilterDateTo').val();
    if (!f || !t) { return; }
    if (f > t) { alert('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด'); return; }
    loadCheckinDailyChart(f, t);
  });

  /* ====================================================
     DataTable — ตัวอย่างข้อมูล
     ==================================================== */
  var sampleRows = [
    ['001284', 'สมชาย ใจดี',      '35', 'ราษฎร์บูรณะ', 'เลิกจ้าง',              '05/05/2026', '<span class="badge badge-warning badge-reason">รอนัดหมาย</span>'],
    ['001283', 'วิภา มานะ',        '28', 'ทุ่งครุ',      'ลาออก',                 '05/05/2026', '<span class="badge badge-success badge-reason">ได้งานแล้ว</span>'],
    ['001282', 'ประเสริฐ สุขใจ',   '42', 'จอมทอง',      'มาตรา 39',              '04/05/2026', '<span class="badge badge-info badge-reason">อยู่ระหว่างดำเนินการ</span>'],
    ['001281', 'นภา รักงาน',       '31', 'บางขุนเทียน', 'ประกอบอาชีพอิสระ',      '04/05/2026', '<span class="badge badge-secondary badge-reason">ปิดเคส</span>'],
    ['001280', 'ธนกร พึ่งตน',      '27', 'บางบอน',      'รับงานไปทำที่บ้าน',     '03/05/2026', '<span class="badge badge-warning badge-reason">รอนัดหมาย</span>'],
    ['001279', 'อรุณี สว่าง',      '39', 'ราษฎร์บูรณะ', 'เลิกจ้าง',              '03/05/2026', '<span class="badge badge-success badge-reason">ได้งานแล้ว</span>'],
    ['001278', 'มานพ เจริญ',       '45', 'ทุ่งครุ',      'อื่นๆ',                 '02/05/2026', '<span class="badge badge-info badge-reason">อยู่ระหว่างดำเนินการ</span>'],
    ['001277', 'รัตนา บุญมี',      '33', 'จอมทอง',      'ลาออก',                 '02/05/2026', '<span class="badge badge-warning badge-reason">รอนัดหมาย</span>'],
    ['001276', 'ศักดิ์ชัย โสภา',   '52', 'บางขุนเทียน', 'เลิกจ้าง',              '01/05/2026', '<span class="badge badge-danger badge-reason">ยกเลิก</span>'],
    ['001275', 'กมลรัตน์ ทองดี',   '24', 'บางบอน',      'ได้งาน',                '01/05/2026', '<span class="badge badge-success badge-reason">ได้งานแล้ว</span>']
  ];

  var $tbody = $('#recentTable tbody');
  sampleRows.forEach(function (row) {
    var tr = '<tr>' + row.map(function (cell) { return '<td>' + cell + '</td>'; }).join('') + '</tr>';
    $tbody.append(tr);
  });

  $('#recentTable').DataTable({
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
    },
    order: [[0, 'desc']],
    pageLength: 5,
    lengthMenu: [5, 10, 25, 50],
    columnDefs: [{ orderable: false, targets: 6 }]
  });

});
