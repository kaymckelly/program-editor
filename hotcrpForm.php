<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Security-Policy" content="connect-src 'self' https://api.crossref.org/";default-src: 'self'>
    <meta charset="utf-8">

    <title>Program Editor</title>

    <!-- Bootstrap -->
   <link href="styles/bootstrap.flatly.css" rel="stylesheet" />

    <!-- jQuery daterangepicker -->
    <link href="./dependencies/jquery-daterange-picker/daterangepicker.min.css" rel="stylesheet" />

    <!-- Styling: Custom -->
    <link href="./styles/main.css" rel="stylesheet" />
    <link href="/libs/fonts/raleway-regular.css" rel="stylesheet"/>
    <link href="/libs/fonts/open-sans-condensed.css" rel="stylesheet"/>

    <!-- Page-specific styling -->
    <style>
      #datePickerRow {
        visibility: hidden;
      }

      /* no idea why this is needed but this is needed */
      #startDate {
        margin-left: -8px;
      }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-expand navbar-custom" id="topNav" role="navigation">
      <span class="navbar-brand" id="navBrand">Program Creator</span>
    </nav>


    <div class="container">
      <p class="instructions text-justify">
        These settings can all be changed later.
      </p>

      <!-- Button to trigger login modal -->
      <div class="row" id="auth-button">
        <div class="col-md-8">
          <button type="button" class="btn btn-success btn-lg" data-toggle="modal" data-target="#authModal">
            Log in for this app
          </button>
        </div>
      </div>

      <!-- TODO: setting up progData in browser memory then gonna do an AJAX call -->
      <textarea id="acceptedPapers" class="d-none" name="accepted" rows="8" cols="80" readonly>
        <?php echo $_POST['accepted'] ?>
      </textarea>

      <div id="confNameInput" class="input-group row my-4">
        <label for="name" class="col-3 col-form-label text-right">Conference name</label>
        <div class="col-6">
          <input id="confName" type="text" class="form-control" name="name" value="<?php if (!empty($_POST['name'])) echo $_POST['name']; ?>" oninput="updateName()" />
          <div class="invalid-feedback">
            There is already a program saved with this name. Please choose another.
          </div>
        </div>
      </div>

      <div class="input-group row my-4">
        <label for="templateSelect" class="col-3 col-form-label text-right">Base conference template</label>
        <div class="col-6">
          <select name="templateSelect" class="form-control" onchange="getConfig(this.value)">
            <option value="" disabled selected>
              Please select a template
            </option>
            <option value="./json/virtual_5day.json">
              Virtual conference (5 days, single track)
            </option>
            <option value="./json/crypto_config.json">
              Crypto (5 days, dual track, bbq)
            </option>
            <option value="./json/ec_config.json">
              Eurocrypt/Asiacrypt (5 days, dual track, banquet)
            </option>
            <option value="./json/pkc_config.json">
              CHES/FSE/PKC/TCC (4 days, single track)
            </option>
            <option value="./json/basic_1day.json">
              Basic one-day workshop
            </option>
            <option value="./json/basic_2day.json">
              Basic two-day workshop
            </option>
            <option value="./json/basic_3day.json">
              Basic three-day conference
            </option>
            <option value="./json/dualtrack_5day.json">
              Dual track, five days
            </option>
            <option value="./json/rump_session.json">
              Rump Session (3 sessions, 30 minutes/session)
            </option>
          </select>
        </div>
      </div>

      <div id="datePickerRow" class="row my-4">
        <!-- TODO: this is misaligned right compared to other labels -->
        <label for="startDate" class="col-3 col-form-label text-right">Conference dates</label>
        <div class="col-9">
          <div id="startEndDatePicker" class="form-inline">
            <input id="startDate" type="text" class="form-control mr-2" name="startDate" autocomplete="off" placeholder="Start date" /> <label class="col-form-label">to</label>
            <input id="endDate" type="text" class="form-control ml-2" name="endDate" autocomplete="off" placeholder="End date" />
          </div>
        </div>
      </div>

      <button id="startEditor" onclick="submitEditorForm()" class="btn btn-success btn-small" disabled>Start editor</button>
    </div>

    <!-- Auth Modal -->
    <div class="modal fade" id="authModal" tabindex="-1" role="dialog" aria-labelledby="authModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title" id="authModalLabel">Log in for IACR Program Creator</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>This app requires you to log in with your IACR reference number
              and password.
            </p>
            <form class="form-horizontal" onsubmit="return false;">
              <div class="form-group">
                <label for="iacrref" class="col-sm-3 control-label">IACR ref #</label>
                <div class="col-sm-8">
                  <input type="text" class="form-control" id="iacrref">
                </div>
              </div>
              <div class="form-group">
                <label for="password" class="col-sm-3 control-label">Password</label>
                <div class="col-sm-8">
                  <input type="password" class="form-control" id="password">
                </div>
              </div>
            </form>
            <p>
              If you have forgotten your IACR reference number or password,
              <a target="_blank" href="https://secure.iacr.org/membership/members/update.html">click here to recover it</a>.
            </p>
          </div>
          <div class="modal-footer">
            <p id="login_progress"></p>
            <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
            <button type="button" onClick="doLogin();this.blur()"class="btn btn-success">Log in</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap scripts -->
    <script src="/libs/js/jquery/3.3.1/jquery.min.js"></script>
    <noscript><h1>This tool will not work without javascript.</h1></noscript>
    <script src="/libs/css/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Dependencies (momentJS, jQuery daterange picker, & datepair) -->
    <script src="/libs/js/moment/moment.min.js"></script>
    <script src="./dependencies/jquery-daterange-picker/jquery.daterangepicker.min.js"></script>
    <script src="./dependencies/datepair/jquery.datepair.min.js"></script>

    <!-- Custom scripts -->
    <script src="./scripts/hotCRP.js"></script>
  </body>
</html>
