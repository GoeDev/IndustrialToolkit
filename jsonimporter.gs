  /*
   * This file is to be used with GDocs Spreadsheets
   * and the Import API provided on eve.fusion-blast.com
   *
   *
   */
  var scriptProperties = PropertiesService.getScriptProperties();
  var userProperties = PropertiesService.getUserProperties();
  var documentProperties = PropertiesService.getDocumentProperties();

  function onOpen() {
    if (scriptProperties.getProperty('RUN') == false)

    function firstTimer;
    var ui = SpreadsheetApp.getUi();

    ui.createMenu('Import Blueprint')
      .addItem('Import Typeid', 'nametoid')
      .addSeparator()
      .addToUi();
  };

  function firstTimer() {
    //Set the system and the regionid to get the Prices
    function showPrompt() {
      var ui = SpreadsheetApp.getUi(); // Same variations.

      var result = ui.prompt(
        'This is the initial setup for this script',
        'Please enter the systemname,',
        'you want to calculate and pull fo:',
        ui.ButtonSet.OK_CANCEL);

      // Process the user's response.
      var button = result.getSelectedButton();
      var text = result.getResponseText();
      if (button == ui.Button.OK) {
        // User clicked "OK".
        Logger.log(text);
        userProperties.setProperty('systemname', text);
      } else if (button == ui.Button.CANCEL) {
        // User clicked "Cancel".
        ui.alert('I didn\'t get the systemname.');
      } else if (button == ui.Button.CLOSE) {
        // User clicked X in the title bar.
        ui.alert('You closed the dialog.');
      }
    }
    //Set charachters Standings and the other shit here

    scriptProperties.setProperty('RUN', true);
  }

  function importer() {
    //Setup shit;
    var c = SpreadsheetApp.getActiveSheet().getActiveCell();
    var row = c.getRow();
    var col = c.getColumn();
    var c_string = c.getValue().toString();
    c_string.replace(' ', '+');
    Logger.log(c_string);

    var json = function http_shit(c_string, REGIONID) {

        //HTTP call
        var options = {
          'muteHttpExceptions': true

        };
        var result = UrlFetchApp.fetch('http://eve.fusion-blast.com/filename.php?typename=' + c_string + '&regionid=' + REGIONID);
        Logger.log(result);

        json = JSON.parse(result);
        Logger.log(json);
      }
      // Import Section
    s.getRange(row + 1, col).setValue(json.typeID);
    // Materials Import section
    var mats = json.mats;
    Logger.log(mats);
    for (var i = 1; i < mats.length; i++) {
      s.getRange(row + i, 2).setValue(mats[i].name);
      s.getRange(row + i, 3).setValue(mats[i].quantity);
      s.getRange(row + i, 4).setValue(mats[i].price);
    }

    //  s.getRange(row +i+1, 4).setValue();
    // sum formula import
    var sheet = SpreadsheetApp.getActiveSheet();
    var sum_form = sheet.getRange(row + mats.length + 1, 5);
    var sum = "=SUM(D" + row + ",D" + (row + mats.length + 1)+")"; 
    Logger.log(sum);
    sum_form.setFormula(sum);
      var invest = "=D" + (row + mats.length + 1) + "*";
    }
