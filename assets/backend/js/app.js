$.extend(theme.PluginDatePicker.defaults, {
  format: "yyyy-mm-dd",
  autoclose: true,
});

(function ($) {
  "use strict";

  // Maintain Scroll Position
  if (typeof localStorage !== "undefined") {
    if (localStorage.getItem("sidebar-left-position") !== null) {
      var initialPosition = localStorage.getItem("sidebar-left-position"),
        sidebarLeft = document.querySelector("#sidebar-left .nano-content");
      sidebarLeft.scrollTop = initialPosition;
    }
  }

  $(".table-default").DataTable({
    dom:
      '<"row align-items-center mb-3"' +
      '<"col-md-6 d-flex align-items-center"l>' +
      '<"col-md-6 d-flex justify-content-md-end"f>>' +
      '<"table-responsive"t>' +
      '<"row mt-3"' +
      '<"col-md-6"i>' +
      '<"col-md-6 d-flex justify-content-md-end"p>>',

    pageLength: 25,
    autoWidth: false,
    ordering: false,

    language: {
      search: "_INPUT_",
      searchPlaceholder: "Search...",
      lengthMenu: "Show _MENU_ entries",
      info: "Showing _START_ to _END_ of _TOTAL_ entries",
      paginate: {
        previous: "Prev",
        next: "Next",
      },
    },
  });

  var table = $(".table-export").DataTable({
    dom:
      '<"row align-items-center mb-3"' +
      '<"col-md-4 d-flex align-items-center"l>' +
      '<"col-md-4 text-center"B>' +
      '<"col-md-4 d-flex justify-content-md-end"f>>' +
      '<"table-responsive"t>' +
      '<"row mt-3"' +
      '<"col-md-6"i>' +
      '<"col-md-6 d-flex justify-content-md-end"p>>',

    ordering: false,
    autoWidth: false,
    responsive: true,
    pageLength: 25,

    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "All"],
    ],

    language: {
      search: "_INPUT_",
      searchPlaceholder: "Search...",
      lengthMenu: "Show _MENU_ entries",
      info: "Showing _START_ to _END_ of _TOTAL_ entries",
      paginate: {
        previous: "Prev",
        next: "Next",
      },
    },

    buttons: [
      {
        extend: "copyHtml5",
        text: '<i class="far fa-copy"></i>',
        className: "btn btn-secondary btn-sm",
      },
      {
        extend: "excelHtml5",
        text: '<i class="far fa-file-excel"></i>',
        className: "btn btn-secondary btn-sm",
      },
      {
        extend: "csvHtml5",
        text: '<i class="far fa-file-alt"></i>',
        className: "btn btn-secondary btn-sm",
      },
      {
        extend: "print",
        text: '<i class="fa fa-print"></i>',
        className: "btn btn-secondary btn-sm",
      },
      {
        extend: "colvis",
        text: '<i class="fas fa-columns"></i>',
        className: "btn btn-secondary btn-sm",
      },
    ],
  });

  $(document).ready(function () {
    // form validation
    $(".validate").validate({
      highlight: function (label) {
        $(label)
          .closest(".form-group")
          .removeClass("has-success")
          .addClass("has-error");
      },
      success: function (label) {
        $(label).closest(".form-group").removeClass("has-error");
        label.remove();
      },
      errorPlacement: function (error, element) {
        var placement = element.closest(".input-group");
        if (!placement.get(0)) {
          placement = element;
        }
        if (error.text() !== "") {
          if (
            element.parent(".checkbox, .radio").length ||
            element.parent(".input-group").length
          ) {
            placement.after(error);
          } else {
            var placement = element.closest("div");
            placement.append(error);
            wrapper: "li";
          }
        }
      },
    });

    $("#frmSubmit").on("submit", function (e) {
      e.preventDefault();
      var btn = $("#savebtn");
      btn.button("loading");
      $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (data) {
          if (data.status == "fail") {
            $.each(data.error, function (index, value) {
              $("#" + index)
                .parents(".form-group")
                .find(".error")
                .html(value);
            });
            btn.button("reset");
          } else {
            window.location.href = data.url;
          }
        },
        error: function () {
          //  alert("Fail")
        },
      });
    });

    // staff bank accountad modal show
    $("#addStaffBank").on("click", function () {
      mfp_modal("#addBankModal");
    });

    // staff bank add data send to the server
    $("#bankaddfrm").on("submit", function (e) {
      var bankaddbtn = $("#bankaddbtn");
      bankaddbtn.button("loading");
      $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (data) {
          if (data.status == "success") {
            window.location.reload(false);
          } else {
            $.each(data.error, function (index, value) {
              $("#a" + index)
                .parents(".form-group")
                .find(".error")
                .html(value);
            });
            bankaddbtn.button("reset");
          }
        },
      });
      e.preventDefault();
    });

    // staff bank edit data send to the server
    $("#bankeditfrm").on("submit", function (e) {
      var bankeditbtn = $("#bankeditbtn");
      bankeditbtn.button("loading");
      $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (data) {
          if (data.status == "success") {
            window.location.reload(false);
          } else {
            $.each(data.error, function (index, value) {
              $("#e" + index)
                .parents(".form-group")
                .find(".error")
                .html(value);
            });
            bankeditbtn.button("reset");
          }
        },
      });
      e.preventDefault();
    });

    // frontend setting captcha status
    $("#captchaStatus").on("change", function () {
      var status = $(this).val();
      if (status == "enable") {
        $("#recaptcha_site_key").show(300);
        $("#recaptcha_secret_key").show(300);
      } else {
        $("#recaptcha_site_key").hide(300);
        $("#recaptcha_secret_key").hide(300);
      }
    });

    // staff document add  modal showing
    $("#addStaffDocuments").on("click", function () {
      $("#docaddfrm #eMsg").slideUp("fast");
      mfp_modal("#add_documents_modal");
    });

    // staff document add data send to the server
    $("#docaddfrm, #patientDocAdd ").on("submit", function (e) {
      e.preventDefault();
      var docsavebtn = $("#docsavebtn");
      docsavebtn.button("loading");
      $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: new FormData(this),
        dataType: "json",
        contentType: false,
        processData: false,
        cache: false,
        success: function (data) {
          if (data.status == "success") {
            window.location.reload(false);
          } else {
            $.each(data.error, function (index, value) {
              $("#a" + index)
                .parents(".form-group")
                .find(".error")
                .html(value);
            });
            docsavebtn.button("reset");
          }
        },
      });
    });

    // staff document edit data send to the server
    $("#doceditfrm, #patientDocEdit").on("submit", function (e) {
      e.preventDefault();
      var doceditbtn = $("#doceditbtn");
      doceditbtn.button("loading");
      $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: new FormData(this),
        dataType: "json",
        contentType: false,
        processData: false,
        cache: false,
        success: function (data) {
          if (data.status == "success") {
            window.location.reload(false);
          } else {
            $.each(data.error, function (index, value) {
              $("#e" + index)
                .parents(".form-group")
                .find(".error")
                .html(value);
            });
            doceditbtn.button("reset");
          }
        },
      });
    });

    // page full screen
    $(".s-expand").on("click", function (e) {
      if (typeof screenfull != "undefined") {
        if (screenfull.enabled) {
          screenfull.toggle();
        }
      }
    });

    if (typeof screenfull != "undefined") {
      if (screenfull.enabled) {
        $(document).on(screenfull.raw.fullscreenchange, function () {
          if (screenfull.isFullscreen) {
            $(".s-expand")
              .find("i")
              .toggleClass("fas fa-expand fas fa-expand-arrows-alt");
          } else {
            $(".s-expand")
              .find("i")
              .toggleClass("fas fa-expand-arrows-alt fas fa-expand");
          }
        });
      }
    }

    // checkbox, radio and selects
    $("#chk-radios-form, #selects-form").each(function () {
      $(this).validate({
        highlight: function (element) {
          $(element)
            .closest(".form-group")
            .removeClass("has-success")
            .addClass("has-error");
        },
        success: function (element) {
          $(element).closest(".form-group").removeClass("has-error");
        },
        errorPlacement: function (error, element) {
          var placement = element.closest("div");
          if (!placement.get(0)) {
            placement = element;
          }
          if (error.text() !== "") {
            placement.append(error);
          }
        },
      });
    });

    // email configurations page
    $("#email_protocol").on("change", function () {
      var mode = $(this).val();
      if (mode == "smtp") {
        $(".smtp").prop("disabled", false);
      } else {
        $(".smtp").prop("disabled", true);
      }
    });

    // script for all checkbox checked / unchecked
    $("#selectAllchkbox").on("change", function (ev) {
      var $chcks = $(".cb-chk-area input[type='checkbox']");
      if ($(this).is(":checked")) {
        $chcks.prop("checked", true).trigger("change");
      } else {
        $chcks.prop("checked", false).trigger("change");
      }
    });

    // referral amount input for all
    $("#percentage_for_all").on("keyup", function () {
      var val = this.value;
      $(".fn_percentage").val(val);
    });

    // employee create form bank info skip
    $("#cbbank_skip").on("change", function () {
      if ($(this).is(":checked")) {
        $("#bank_details_form").hide("slow");
      } else {
        $("#bank_details_form").show("slow");
      }
    });

    // patient age calculation
    $("#patient_birthday").on("change", function (e) {
      var dateOfBirth = this.value;
      var dob = new Date(dateOfBirth);
      var endDt = new Date();
      var age =
        new Date(endDt.getTime() - dob.getTime()).getUTCFullYear() - 1970;
      $("#age").val(age);
    });

    // patient and staff authentication
    $("#showPassword").on("click", function () {
      var password = $("#reset_password");
      if (password.attr("type") == "password") {
        password.prop("type", "text");
      } else {
        password.prop("type", "password");
      }
    });

    $("#cb_authentication").on("click", function () {
      var password = $(".password");
      if (this.checked) {
        $(".password").val("").attr("disabled", true);
      } else {
        $(".password").removeAttr("disabled");
      }
    });

    // staff update password send form data to the server
    $("#staffPassUpdate").on("click", function () {
      var $this = $(this);
      $this.button("loading");
      var user_id = $("#user_id").val();
      var password = $("#reset_password").val();
      var cb_authentication = $("#cb_authentication:checked").val();
      $.ajax({
        url: base_url + "employee/change_password",
        type: "POST",
        data: {
          user_id: user_id,
          password: password,
          authentication: cb_authentication,
        },
        dataType: "json",
        success: function (res) {
          if (res.status == "success") {
            $.magnificPopup.close();
            window.location.reload(false);
          } else {
            $("#password-msg").html(res.msg);
            $("#password-msg").parent().addClass("has-error");
          }
          $this.button("reset");
        },
      });
    });

    // staff update password send form data to the server
    $("#userchangeexpiretime").on("click", function () {
      var $this = $(this);
      $this.button("loading");
      var user_id = $("#user_id").val();
      var hour = $("#hour").val();
      var minute = $("#minute").val();
      var ampm = $("#ampm").val();
      $.ajax({
        url: base_url + "employee/change_expiretime",
        type: "POST",
        data: {
          user_id: user_id,
          hour: hour,
          minute: minute,
          ampm: ampm,
        },
        dataType: "json",
        success: function (res) {
          if (res.status == "success") {
            $.magnificPopup.close();
            window.location.reload(false);
          } else {
            $("#password-msg").html(res.msg);
            $("#password-msg").parent().addClass("has-error");
          }
          $this.button("reset");
        },
      });
    });

    // patient update password send form data to the server
    $("#patientPassUpdate").on("click", function () {
      var $this = $(this);
      $this.button("loading");
      var user_id = $("#patient_id").val();
      var password = $("#reset_password").val();
      var cb_authentication = $("#cb_authentication:checked").val();
      $.ajax({
        url: base_url + "patient/change_password",
        type: "POST",
        data: {
          user_id: user_id,
          password: password,
          authentication: cb_authentication,
        },
        dataType: "json",
        success: function (res) {
          if (res.status == "success") {
            $.magnificPopup.close();
            window.location.reload(false);
          } else {
            $("#password-msg").html(res.msg);
            $("#password-msg").parent().addClass("has-error");
          }
          $this.button("reset");
        },
      });
    });

    // permission page select all
    $("#all_view").on("click", function () {
      if ($(this).is(":checked")) {
        $(".cb_view").prop("checked", true);
      } else {
        $(".cb_view").prop("checked", false);
      }
    });

    $("#all_add").on("click", function () {
      if ($(this).is(":checked")) {
        $(".cb_add").prop("checked", true);
      } else {
        $(".cb_add").prop("checked", false);
      }
    });

    $("#all_edit").on("click", function () {
      if ($(this).is(":checked")) {
        $(".cb_edit").prop("checked", true);
      } else {
        $(".cb_edit").prop("checked", false);
      }
    });

    $("#all_delete").on("click", function () {
      if ($(this).is(":checked")) {
        $(".cb_delete").prop("checked", true);
      } else {
        $(".cb_delete").prop("checked", false);
      }
    });

    // Reusable Global Password Show/Hide Toggle
    $(document).on("click", ".toggle-password", function (e) {
      e.preventDefault();
      var targetSelector = $(this).attr("data-target");
      if (targetSelector) {
        // Handle target specified without a '#' prefix (e.g. data-target="password")
        if (
          typeof targetSelector === "string" &&
          targetSelector.charAt(0) !== "#" &&
          targetSelector.charAt(0) !== "."
        ) {
          targetSelector = "#" + targetSelector;
        }
        var targetInput = $(targetSelector);
        var icon = $(this).find("i");
        if (targetInput.length) {
          if (targetInput.attr("type") === "password") {
            targetInput.attr("type", "text");
            icon.removeClass("fa-eye").addClass("fa-eye-slash");
          } else {
            targetInput.attr("type", "password");
            icon.removeClass("fa-eye-slash").addClass("fa-eye");
          }
        }
      }
    });
  });

  // frontend menu external link enable/disable
  $(".ext_url").change(function () {
    var v = this.checked ? 1 : 0;
    if (v) {
      $("#external_link").prop("disabled", false);
    } else {
      $("#external_link").prop("disabled", true);
    }
  });

  // switch for service status
  $(".service_switch_menu").on("change", function () {
    var state = $(this).prop("checked");
    var menu_id = $(this).data("menu-id");
    $.ajax({
      type: "POST",
      url: base_url + "myservices/status",
      data: { menu_id: menu_id, status: state },
      dataType: "html",
      success: function (data) {
        swal({
          type: "success",
          title: "Successfully",
          text: data,
          showCloseButton: true,
          focusConfirm: false,
          buttonsStyling: false,
          confirmButtonClass: "btn btn-default swal2-btn-default",
          //footer: '*Note : You can undo this action at any time'
        });
      },
    });
  });

  // switch for frontend menu
  $(".switch_menu").on("change", function () {
    var state = $(this).prop("checked");
    var menu_id = $(this).data("menu-id");
    $.ajax({
      type: "POST",
      url: base_url + "frontend/menu/status",
      data: { menu_id: menu_id, status: state },
      dataType: "html",
      success: function (data) {
        swal({
          type: "success",
          title: "Successfully",
          text: data,
          showCloseButton: true,
          focusConfirm: false,
          buttonsStyling: false,
          confirmButtonClass: "btn btn-default swal2-btn-default",
          footer: "*Note : You can undo this action at any time",
        });
      },
    });
  });

  // switch for language
  $(".switch_lang").on("change", function () {
    var state = $(this).prop("checked");
    var lang_id = $(this).data("lang");
    $.ajax({
      type: "POST",
      url: base_url + "language/status",
      data: { lang_id: lang_id, status: state },
      dataType: "html",
      success: function (data) {
        swal({
          type: "success",
          title: "Successfully",
          text: data,
          showCloseButton: true,
          focusConfirm: false,
          buttonsStyling: false,
          confirmButtonClass: "btn btn-default swal2-btn-default",
          footer: "*Note : You can undo this action at any time",
        });
      },
    });
  });

  // modal dismiss
  $(document).on("click", ".modal-dismiss", function (e) {
    e.preventDefault();
    $.magnificPopup.close();
  });

  // date range picker
  if ($(".daterange").length) {
    $(".daterange").daterangepicker({
      opens: "left",
      locale: { format: "YYYY/MM/DD" },
      ranges: {
        Today: [moment(), moment()],
        Yesterday: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "Last 7 Days": [moment().subtract(6, "days"), moment()],
        "Last 30 Days": [moment().subtract(29, "days"), moment()],
        "This Month": [moment().startOf("month"), moment().endOf("month")],
        "Last Month": [
          moment().subtract(1, "month").startOf("month"),
          moment().subtract(1, "month").endOf("month"),
        ],
        "This Year": [moment().startOf("year"), moment().endOf("year")],
        "Last Year": [
          moment().subtract(1, "year").startOf("year"),
          moment().subtract(1, "year").endOf("year"),
        ],
      },
    });
  }

  // dropify basic configurations
  if (typeof Dropify != "undefined") {
    if ($(".dropify").length) {
      $(".dropify").dropify();
    }
  }

  // month and year mode datepicker
  if ($(".monthyear").length) {
    $(".monthyear").datepicker({
      orientation: "bottom",
      autoclose: true,
      startView: 1,
      format: "yyyy-mm",
      minViewMode: 1,
    });
  }

  // customize ckeditor
  if ($("#editor").length) {
    CKEDITOR.replace("editor");
  }

  if ($(".editor").length) {
    $(".editor").ckeditor();
  }
})(jQuery);

// staff bank account edit modal show
function editStaffBank(id) {
  $.ajax({
    url: base_url + "ajax/bank_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#ebank_id").val(res.id);
      $("#ebank_name").val(res.bank_name);
      $("#eholder_name").val(res.holder_name);
      $("#ebank_branch").val(res.bank_branch);
      $("#ebank_address").val(res.bank_address);
      $("#eifsc_code").val(res.ifsc_code);
      $("#eaccount_no").val(res.account_no);
      mfp_modal("#editBankModal");
    },
  });
}

// staff documents edit modal show
function editStaffDocument(id) {
  $.ajax({
    url: base_url + "ajax/staff_document_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#edocument_id").val(res.id);
      $("#exist_file_name").val(res.enc_name);
      $("#edocument_title").val(res.title);
      $("#edocument_category").val(res.category_id).trigger("change");
      $("#edocuments_remarks").val(res.remarks);
      mfp_modal("#editDocModal");
    },
  });
}

// staff documents edit modal show
function editPatientDocument(id) {
  $.ajax({
    url: base_url + "ajax/patient_document_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#edocument_id").val(res.id);
      $("#exist_file_name").val(res.enc_name);
      $("#edocument_title").val(res.title);
      $("#edocument_category").val(res.type);
      $("#edocuments_remarks").val(res.remarks);
      mfp_modal("#editDocModal");
    },
  });
}

// appointment consultation fees calculation
function netBillCalculation() {
  var discount = $("#discount").val();
  var consultation_fees = $("#consultation_fees").val();
  var net_payable = (consultation_fees - discount).toFixed(2);
  $("#net_payable").val(net_payable);
}

// inventory product purchase grand total calculation
function grandTotalCalculatePur() {
  var netGrandTotal = 0;
  $(".net_sub_total").each(function () {
    netGrandTotal += read_number(this.value);
  });
  $("#netGrandTotal").val(netGrandTotal.toFixed(2));

  var grandTotal = 0;
  $(".sub_total").each(function () {
    grandTotal += read_number(this.value);
  });
  $("#grandTotal").val(grandTotal.toFixed(2));

  var total_discount = 0;
  $(".purchase_discount").each(function () {
    total_discount += read_number(this.value);
  });
  $("#totalDiscount").val(total_discount);
}

// test bill grand total calculation
function grandTotalCalculateBill() {
  var total_amount = 0;
  var discount_amount = 0;
  $(".cont_gra_total").each(function () {
    total_amount += read_number(this.value);
  });
  $(".items_discount").each(function () {
    discount_amount += read_number(this.value);
  });
  var aftergrand_total = read_number(total_amount - discount_amount);
  var tax_amount = read_number($("#tax_amount").val());
  var net_amount = aftergrand_total + tax_amount;
  $("#sub_total_amount").val(total_amount.toFixed(2));
  $("#total_discount").val(discount_amount.toFixed(2));
  $("#net_amount").val(net_amount.toFixed(2));
}

// test bill tax calculation
function tax_update() {
  var sub_total_amount = read_number($("#sub_total_amount").val());
  var total_discount = read_number($("#total_discount").val());
  var tax_percent = read_number($("#tax_percent").val());
  var tax_amount = (tax_percent / 100) * sub_total_amount;
  var net_amount = sub_total_amount - total_discount + tax_amount;
  $("#tax_amount").val(tax_amount.toFixed(2));
  $("#net_amount").val(net_amount.toFixed(2));
}

// test bill discount calculation
function discount_update(rowid) {
  var unit_price = read_number($("#unit_price" + rowid).val());
  var discount_percent = read_number($("#dis_percent" + rowid).val());
  var discount_amount = (discount_percent / 100) * unit_price;
  $("#dis_amount" + rowid).val(discount_amount.toFixed(2));
  $("#total_price" + rowid).val(unit_price - discount_amount);
  grandTotalCalculateBill();
}

function read_number(inputelm) {
  if (isNaN(inputelm) || inputelm.length == 0) {
    return 0;
  } else {
    return parseFloat(inputelm);
  }
}

// investigation create get report template
function getReportTemplate(template_id) {
  $.ajax({
    type: "POST",
    url: base_url + "ajax/getReportTemplate",
    data: { id: template_id },
    dataType: "html",
    success: function (data) {
      CKEDITOR.instances["editor"].setData(data);
    },
  });
}

// get department details
function getDepartmentDetails(id) {
  $.ajax({
    url: base_url + "ajax/department_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#edepartment_id").val(res.id);
      $("#cdepartment_name").val(res.name);
      mfp_modal("#modal");
    },
  });
}

// get designation details
function getDesignationDetails(id) {
  $.ajax({
    url: base_url + "ajax/designation_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#edesignation_id").val(res.id);
      $("#edesignation_name").val(res.name);
      mfp_modal("#modal");
    },
  });
}

// get pathology test details
function getTestCategory(id) {
  $.ajax({
    url: base_url + "ajax/test_category_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#ecategory_id").val(res.id);
      $("#ecategory_name").val(res.name);
      mfp_modal("#modal");
    },
  });
}

function getStaffList(role_id, user_id) {
  if (role_id != "") {
    $.ajax({
      type: "POST",
      url: base_url + "ajax/get_staff_list",
      data: { role_id: role_id, user_id: user_id },
      dataType: "html",
      success: function (data) {
        $("#user_id").html(data);
      },
    });
  }
}

// get patient category details
function getPatientCategory(id) {
  $.ajax({
    url: base_url + "client/categoryDetails",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#ecategory_id").val(res.id);
      $("#ecategory_name").val(res.name);
      mfp_modal("#modal");
    },
  });
}

// get payslip details
function getPayslip(id) {
  $.ajax({
    url: base_url + "ajax/get_payslip_details",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#invoice_print").html(data);
      mfp_modal("#modal");
    },
  });
}

// get leave category
function getLeaveCategory(id) {
  $.ajax({
    url: base_url + "ajax/get_leave_category_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (data) {
      $("#ecategory_id").val(data.id);
      $("#eleave_category").val(data.name);
      $("#eleave_days").val(data.days);
      mfp_modal("#modal");
    },
  });
}

// get unit category
function getUnitDetails(id) {
  $.ajax({
    url: base_url + "ajax/product_unit_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#eunit_id").val(res.id);
      $("#eunit_name").val(res.name);
      mfp_modal("#modal");
    },
  });
}

// get product category details
function getProductCategory(id) {
  console.log();
  $.ajax({
    url: base_url + "ajax/product_category_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#ecategory_id").val(res.id);
      $("#ecategory_name").val(res.name);
      mfp_modal("#modal");
    },
  });
}

// get callerid details
function getCallerid(id) {
  $.ajax({
    url: base_url + "voice/callerid_edit",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#ecallerid_id").val(res.id);
      $("#edate_time").val(res.date_time);
      $("#euser_id").val(res.assign_to);
      $("#ecaller_id").val(res.caller_id);
      $("#echannels").val(res.channels);
      $("#estatus").val(res.status);
      mfp_modal("#modal");
    },
  });
}

// get contact details
function getContact(id) {
  $.ajax({
    url: base_url + "contacts/contact_edit",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#econtact_id").val(res.id);
      $("#edatetime").val(res.datetime);
      $("#euser_id").val(res.assigned_to);
      $("#ename").val(res.name);
      $("#ephone").val(res.phone);
      $("#eaddress").val(res.address);
      $("#eemail").val(res.email);
      $("#egroup_name").val(res.group_name);
      mfp_modal("#modal");
    },
  });
}

// get trunk details
function getTrunk(id) {
  $.ajax({
    url: base_url + "voice/trunk_edit",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#etrunk_id").val(res.id);
      $("#edate_time").val(res.date_time);
      $("#euser_id").val(res.assign_to);
      $("#etrunk_name").val(res.trunk_name);
      $("#etrunk_clid_name").val(res.trunk_clid_name);
      $("#etrunk_type").val(res.trunk_type);
      $("#etrunk_channel_limit").val(res.trunk_channel_limit);
      $("#estatus").val(res.status);
      mfp_modal("#modal");
    },
  });
}

// get rateProfile details
function getRateProfile(id) {
  $.ajax({
    url: base_url + "rateprofile/rate_profile_edit",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#eid").val(res.id);
      $("#ename").val(res.name);
      $("#etype").val(res.type);
      $("#eprefix").val(res.prefix);
      $("#ebill_type").val(res.bill_type);
      $("#erate").val(res.rate);
      $("#epulse").val(res.pulse);
      $("#emax_duration").val(res.max_duration);
      mfp_modal("#modal");
    },
  });
}

// get rateProfile details
function getRateProf(id) {
  $.ajax({
    url: base_url + "employee/get_rate_profile",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#quick_rate_profile").html(data);
      mfp_modal("#modal");
    },
  });
}

// get packages details
function getPackages(id, selectedPackageId = null) {
  $.ajax({
    url: base_url + "client/get_packages",
    type: "POST",
    data: { id: id, seletedPackageId: selectedPackageId },
    dataType: "html",
    success: function (data) {
      $("#quick_packages").html(data);
      mfp_modal("#packageModal");
    },
  });
}

// get rateProfile details
function addpayment(id) {
  $.ajax({
    url: base_url + "client/add_payment",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (html) {
      console.log(html);
      $("#quick_add_payment").html(html);
      mfp_modal("#modal2");
    },
  });
}

// deduce payment on user list
function deducePayment(id) {
  $.ajax({
    url: base_url + "client/deduce_payment",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#quick_deduce_payment").html(data);
      mfp_modal("#deducePaymentModel");
    },
  });
}

// Add Payment EMP
function addpayment_emp(id) {
  $.ajax({
    url: base_url + "employee/add_payment",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (html) {
      console.log(html);
      $("#quick_add_payment_modal_emp").html(html);
      mfp_modal("#modal_emp");
    },
  });
}

// Deduce payment EMP
function deducePayment_emp(id) {
  $.ajax({
    url: base_url + "employee/deduce_payment",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#quick_deduce_payment_emp").html(data);
      mfp_modal("#deducePaymentModel_emp");
    },
  });
}

// get ami details
function getAmi(id) {
  $.ajax({
    url: base_url + "ami/ami_edit",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#eami_id").val(res.id);
      $("#ehost").val(res.host);
      $("#eport").val(res.port);
      $("#eusername").val(res.username);
      $("#epassword").val(res.password);
      $("#estatus").val(res.status);
      mfp_modal("#modal");
    },
  });
}

// get TTS details
function getTTS(id) {
  $.ajax({
    url: base_url + "voice/tts_edit",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#etts_id").val(res.id);
      $("#edate_time").val(res.date_time);
      $("#euser_id").val(res.assign_to);
      $("#etts_name").val(res.tts_name);
      $("#etts_text").val(res.tts_text);
      $("#estatus").val(res.status);
      mfp_modal("#modal");
    },
  });
}

// get leave approvel details
function getApprovelLeaveDetails(id) {
  $.ajax({
    url: base_url + "ajax/getApprovelLeaveDetails",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#quick_view").html(data);
      mfp_modal("#modal");
    },
  });
}

// get salary template
function getSalaryTemplate(id) {
  $.ajax({
    url: base_url + "ajax/get_salary_template_details",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#quick_view").html(data);
      mfp_modal("#modal");
    },
  });
}

// get ConfigCmp template
function getConfigCmp(id) {
  $.ajax({
    url: base_url + "voice/get_config_cmp_details",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#quick_view_tray").html(data);
      mfp_modal("#modal");
    },
  });
}

// get ConfigCmp template
function getView(id) {
  $.ajax({
    url: base_url + "voice/get_list_view",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#quick_list_view").html(data);
      mfp_modal("#modal2");
    },
  });
}

// get running status
function getRunningStatus(id) {
  $.ajax({
    url: base_url + "voice/schedule_campaign_result",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#running_status_view").html(data);
      mfp_modal("#modal3");
    },
  });
}

// get QuickCall template
function getQuickCall(id) {
  $.ajax({
    url: base_url + "voice/get_quick_call_details",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#id").html(data);
      set_alert("success", translate("Call Re-Initiated successfully"));
    },
  });
}

// get voucher head
function getVoucherHead(id) {
  $.ajax({
    url: base_url + "ajax/voucher_head_details",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (data) {
      $("#evoucher_head_id").val(data.id);
      $("#evoucher_head").val(data.name);
      mfp_modal("#modal");
    },
  });
}

// get voucher head list
function getHeadList(voucher_type, head_id) {
  if (voucher_type != "") {
    $.ajax({
      type: "POST",
      url: base_url + "ajax/get_voucher_head_list",
      data: {
        voucher_type: voucher_type,
        voucher_head_id: head_id,
      },
      dataType: "html",
      success: function (data) {
        $("#voucher_head_id").html(data);
      },
    });
  }
}

function getStaffLeaveDetails(id) {
  $.ajax({
    url: base_url + "ajax/get_staff_leave_details",
    type: "POST",
    data: { id: id },
    dataType: "html",
    success: function (data) {
      $("#quick_view").html(data);
      mfp_modal("#modal");
    },
  });
}

function getProductByCategory(category_id, product_id) {
  if (category_id != "") {
    $.ajax({
      type: "POST",
      url: base_url + "ajax/get_product_by_category",
      data: {
        category_id: category_id,
        product_id: product_id,
      },
      dataType: "html",
      success: function (data) {
        $("#in_product_id").html(data);
      },
    });
  }
}

// attendance select all
function fn_select_all(val) {
  if (val == 1) {
    $(".spresent").prop("checked", true);
  }
  if (val == 2) {
    $(".sabsent").prop("checked", true);
  }
  if (val == 3) {
    $(".sholiday").prop("checked", true);
  }
  if (val == 4) {
    $(".slate").prop("checked", true);
  }
}

// print function
function fn_printElem(elem) {
  var oContent = document.getElementById(elem).innerHTML;
  var frame1 = document.createElement("iframe");
  frame1.name = "frame1";
  frame1.style.position = "absolute";
  frame1.style.top = "-1000000px";
  document.body.appendChild(frame1);
  var frameDoc = frame1.contentWindow
    ? frame1.contentWindow
    : frame1.contentDocument.document
      ? frame1.contentDocument.document
      : frame1.contentDocument;
  frameDoc.document.open();
  //Create a new HTML document.
  frameDoc.document.write("<html><head><title></title>");
  frameDoc.document.write(
    '<link rel="stylesheet" href="' +
      base_url +
      'assets/vendor/bootstrap/css/bootstrap.min.css">',
  );
  frameDoc.document.write(
    '<link rel="stylesheet" href="' +
      base_url +
      'assets/css/custom-style.css">',
  );
  frameDoc.document.write(
    '<link rel="stylesheet" href="' + base_url + 'assets/css/app.css">',
  );
  frameDoc.document.write("</head><body>");
  frameDoc.document.write(oContent);
  frameDoc.document.write("</body></html>");
  frameDoc.document.close();
  setTimeout(function () {
    window.frames["frame1"].focus();
    window.frames["frame1"].print();
    frame1.remove();
  }, 500);
  return true;
}

// show ajax response on request success
function ajaxModal(url) {
  $.ajax({
    url: url,
    success: function (data) {
      $.magnificPopup.open({
        items: {
          src: data,
          type: "inline",
        },
        fixedContentPos: false,
        fixedBgPos: true,
        overflowY: "auto",
        closeBtnInside: true,
        preloader: false,
        midClick: true,
        removalDelay: 400,
        mainClass: "my-mfp-zoom-in",
        modal: true,
      });
    },
  });
}

// get trunk details
function getContactAssign(id) {
  $.ajax({
    url: base_url + "contacts/contacts_assign_edit",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      $("#e_id").val(res.id);
      $("#ecreated_date").val(res.created_date);
      $("#econtact_group").val(res.contact_group);
      $("#eassigned_to").val(res.assigned_to);
      $("#estatus").val(res.status);
      mfp_modal("#modal");
    },
  });
}

// modal with css animation
function mfp_modal(data) {
  $.magnificPopup.open({
    items: {
      src: data,
      type: "inline",
    },
    fixedContentPos: false,
    fixedBgPos: true,
    overflowY: "auto",
    closeBtnInside: true,
    preloader: false,
    midClick: true,
    removalDelay: 400,
    mainClass: "my-mfp-zoom-in",
    modal: true,
  });
}
