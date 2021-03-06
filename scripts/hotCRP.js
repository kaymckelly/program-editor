// start with conference name in progData
var progData = {name: document.getElementById('confName').value};

// Returns new integer for use as id on talks and sessions
function createUniqueId() {
  progData.config.uniqueIDIndex++;
  return progData.config.uniqueIDIndex;
}

function updateName() {
  progData.name = document.getElementById('confName').value;
}

// set start/end dates in progData
function setDates(startDate) {
  var day = moment(startDate);
  for (var i = 0; i < progData.days.length; i++) {
    progData.days[i].date = day.format('YYYY-MM-DD');
    day.add(1, 'days');
  }
}

// jQuery date picker
function hotCrpDatePicker(numDays) {
  $('#startEndDatePicker').dateRangePicker( {
    separator : ' to ',
    autoClose: true,
    minDays: numDays,
    maxDays: numDays,
    getValue: function() {
      if ($('#startDate').val() && $('#endDate').val()) {
        return $('#startDate').val() + ' to ' + $('#endDate').val();
      } else {
        return '';
      }
    },
    setValue: function(s,s1,s2) {
      $('#startDate').val(s1);
      $('#endDate').val(s2);
      setDates(s1);
      $('#startEditor').removeAttr('disabled');
    }
  });
}

// Validates that data conforms to progData schema
function setProgData(data) {
  if (!data.config.uniqueIDIndex) {
    data.config.uniqueIDIndex = 0;
  }
  progData = data;
  var days = progData.days;

  for (var i = 0; i < days.length; i++) {
    var timeslots = days[i]['timeslots'];
    for (var j = 0; j < timeslots.length; j++) {
      if (timeslots[j].sessions) {
        for (var k = 0; k < timeslots[j]['sessions'].length; k++) {
          timeslots[j]['sessions'][k].id = 'session-' + createUniqueId();
        }
        if(timeslots[j]['sessions'].length > 1) {
          timeslots[j]['twosessions'] = true;
        }
      }
    }
  }
  return data;
}

// parse JSON file to create initial program structure
function getConfig(name) {
  $.getJSON(name, function(data) {
    setProgData(data);

    // updates name and add accepted papers
    progData.name = document.getElementById('confName').value;
    let acceptedPapers = JSON.parse(document.getElementById('acceptedPapers').value).acceptedPapers;

    acceptedPapers.forEach((paper, i) => {
      paper.id = 'talk-' + createUniqueId();
    });

    progData.config.unassigned_talks = [
      {'id': 'category-' + createUniqueId(),
      'name': 'Uncategorized',
      'talks': acceptedPapers}
    ];

    // add dates to progData and show datepicker
    hotCrpDatePicker(progData.days.length);
    $('#datePickerRow').css('visibility', 'visible');

    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    document.getElementById('startEditor').disabled = true;
  })
    .fail(function(jqxhr, textStatus, error) {
      console.dir(jqxhr);
      warningBox('There was a problem with this conference template. Please try another.');
  });
}

// is user logged in?
function checkLogin() {
  $.ajax({
    type: "GET",
    url: "ajax.php",
    success: function(data, textStatus, jqxhr) {
      if (data.hasOwnProperty('username')) {
        $('#authModal').modal('hide');
      } else {
        $('#auth-button').show(500);
        $('#authModal').modal();
      }
    },
    error: function(jqxhr, textStatus, error) {
      console.dir(jqxhr);
      console.dir(error);
    }
  });
}

// log user in
function doLogin() {
  // This send an AJAX POST and receives userid and userName in response
  // if it works.
  var iacrref = $('#iacrref').val();
  var password = $('#password').val();
  $.ajax({
    type: "POST",
    url: "ajax.php",
    data: {'iacrref': iacrref, 'password': password},
    beforeSend: function(jqXHR, settings) {
      console.log('before send');
      $('#login_progress').removeClass('login-alert');
      $('#login_progress').text('Checking...');
      return true;
    },
    dataType: "json",
    success: function(data, textStatus, jqxhr) {
      console.dir(data);
      if (data.hasOwnProperty('username')) {
        $('#login_progress').text('');
        $('#authModal').modal('hide');
        if ($('#auth-button').is(':visible')) {
          $('#auth-button').hide(500);
        }
      } else {
        $('#login_progress').addClass('login-alert');
        $('#login_progress').text(data['error']);
      }
    },
    error: function(jqxhr, textStatus, error) {
      $('#login_status').text('An error occurred:' + textStatus);
      console.dir(jqxhr);
      console.dir(error);
    }
  });
}

function submitEditorForm() {
  if (document.getElementById('startEditor').disabled) {
    console.log('Button is disabled');
    return;
  }
  fetch('receiveFromHotCRP.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'progData=' + encodeURIComponent(JSON.stringify(progData))
  }).then((response)=>response.json())
  .then((data)=>{
    console.dir(data);
    // TODO: better error displays to user
    if (data.hasOwnProperty('error')) {
      // looking for duplicates in db
      if (data.hasOwnProperty('errorInfo') && data.errorInfo[1] == 1062) {
        console.dir(data.errorInfo);
        document.getElementById('confName').classList.add('is-invalid');
      }
    }
    else {
      if (data.hasOwnProperty('database_id')) {
        let url = window.location.href;
        window.location.href = url.substring(0, url.lastIndexOf("/")) + '?id=' + data.database_id;
      }
    }
  }).catch((e)=>console.dir(e));
}

$(document).ready(function() {
  checkLogin();
})
