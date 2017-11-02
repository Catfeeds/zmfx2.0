$(document).ready(function () {
    // 样式控制
    $('#news_list #FL_Second ul li').each(function () {
        if ($(this).attr('id') === 'shopAddress') {
            $(this).addClass('active');
        } else {
            $(this).removeClass('active');
        }
    });
    // 取消按钮
    $("#cancel").click(function () {
        $('#theForm')[0].reset();
        $("#address_edit").css('display', 'none');
    });
// 省市区地址
    function address(sel, hl) {
        var parent_id = $(sel).children('option:selected').val(), html, i;
        if (parent_id !== 0) {
            $.get("/Admin/Public/getRegion",{'parent_id': parent_id}, function (data) {
                html = '<option value="0">请选择</option>';
                for (i = 0; i < data.length; i++) {
                    html += '<option value="' + data[i].region_id + '">' + data[i].region_name + '</option>';
                }
                $(hl).html(html);
            });
        }
    }
    $('#selCountries').change(function () { address('#selCountries', '#selProvinces'); });
    $('#selProvinces').change(function () { address('#selProvinces', '#selCities'); });
    $('#selCities').change(function () { address('#selCities', '#selDistricts'); });
});
// 显示添加地址界面
function show_address_edit() {
    var addForm = $('#address_edit');
    $('#theForm')[0].reset();
    if (addForm.css('display') === 'none') {
        $('#address_edit').css('display', "table-row");
    } else {
        $('#address_edit').css('display', 'none');
    }
}
function trim(text) {
    if (typeof text === "string") {
        return text.replace(/^\s*|\s*$/g, "");
    } else {
        return text;
    }
}
function isEmpty(val) {
  var result = true;
  switch (typeof val) {
  case 'string':
    if (val === '0') {
      result = true;
    } else {
      result = trim(val).length === 0 ? true : false;
    }
    break;
  case 'number':
    result = val === 0;
    break;
  case 'object':
    result = val === null;
    break;
  case 'array':
    result = val.length === 0;
    break;
  default:
    result = true;
  }
  return result;
}
function isNumber(val) {
  var reg = /^[\d|\.|,]+$/;
  return reg.test(val);
}
function isTel(tel) {
  var reg = /^[\d|\-|\s|\_]+$/; //只允许使用数字-空格等
  return reg.test(tel);
}
// 检查地址数据
function checkConsignee(frm) {
  var msg = [], err = false, message;
  if (frm.elements['selCountries'] && isEmpty(frm.elements['selCountries'].value)) {
    msg.push("{$Think.lang.L9080}");
    err = true;
  }
  if (frm.elements['selProvinces'] && isEmpty(frm.elements['selProvinces'].value)) {
    err = true;
    msg.push("{$Think.lang.L9081}");
  }
  if (frm.elements['selCities'] && isEmpty(frm.elements['selCities'].value)) {
    err = true;
    msg.push("{$Think.lang.L9082}");
  }
  if (frm.elements['selDistricts'] &&  isEmpty(frm.elements['selDistricts'].value)) {
    err = true;
    msg.push('{$Think.lang.L9083}');
  }
  if (isEmpty(frm.elements['name'].value)) {
    err = true;
    msg.push('{$Think.lang.L9084}');
  }
  if (frm.elements['address'] && isEmpty(frm.elements['address'].value)) {
    err = true;
    msg.push('{$Think.lang.L9085}');
  }
  if (frm.elements['code'] && (!isNumber(frm.elements['postalcode'].value))) {
    err = true;
    msg.push('{$Think.lang.L9086}');
  }
  if ((frm.elements['phone'] && (!isTel(frm.elements['phone'].value)))) {
    err = true;
    msg.push('{$Think.lang.L9087}');
  }
  if (err) {
    message = msg.join("\n");
    alert(message);
  }
  return !err;
}
// 添加后的回调
function refForm(data) {
  var obj = jQuery.parseJSON(data);
  if (obj.error === 'yes') {
    alert(obj.msg);
  } else {
    alert(obj.msg);
    window.location.href = obj.backUrl;
  }
}
// 删除地址回调
function deleteAddCallback(obj) {  
  if (obj.error === 'yes') {
    alert(obj.msg);
  } else {
    alert(obj.msg);
    $('#' + obj.address_id).remove();
  }
}
// 设置默认地址回调
function refSetDefaultAddress(result) {
  var obj = jQuery.parseJSON(result);
  if (obj.error === 'no') {
    $('.defaultAddress').each(function () {
      $(this)[0].checked = false;
      if ($(this)[0].value === obj.address_id) {
        $(this)[0].checked = 'checked';
      }
    });
  }
}
// 删除地址
function deleteAdd(address_id) {
  $.post("/Home/User/deleteUserAdd", {address_id:address_id},deleteAddCallback);
}
// 设置默认地址
function setDefaultAddress(address_id) {
  _ajax("{:U('Home/User/setDefAddress')}", 'address_id=' + address_id, 'post', refSetDefaultAddress, false);
}