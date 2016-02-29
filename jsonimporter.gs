  /*
   * This file is to be used with GDocs Spreadsheets
   * and the Import API provided on eve.fusion-blast.com
   *
   * @param {"Name"} input the name of the type.
   * @return the output will be written into the cells...
   * @customfunction
   *
   */
  var scriptProperties = PropertiesService.getScriptProperties();
  var userProperties = PropertiesService.getUserProperties();
  var documentProperties = PropertiesService.getDocumentProperties();

/*  function onOpen() {
    if (scriptProperties.getProperty('RUN') == false)

    function firstTimer;
    var ui = SpreadsheetApp.getUi();

    ui.createMenu('Import Blueprint')
      .addItem('Import Name', 'importer')
      .addSeparator()
      .addToUi();
  };
  function showPrompt(display_text) {
    var ui = SpreadsheetApp.getUi(); // Same variations.

    var result = ui.prompt(
      display_text,
      ui.ButtonSet.OK_CANCEL);

    // Process the user's response.
    var button = result.getSelectedButton();
    var text = result.getResponseText();
    if (button == ui.Button.OK) {
      // User clicked "OK".
      // I don't want to implement datachecking right now... :)
      Logger.log(text);
      return text;
    } else if (button == ui.Button.CANCEL) {
      // User clicked "Cancel".
      Logger.log('You canceled the Setup');
    } else if (button == ui.Button.CLOSE) {
      // User clicked X in the title bar.
      Logger.log('User closed setup dialog');
    }
  }

function firstTimer() {
    //Set the system and the regionid to get the Prices
    var systemname = showPrompt('This is to import the Systemname to calculate for:')
    userProperties.setProperty('systemname',systemname);
    var systemid;
    var regionid;
    var ind_sys_index;
    userProperties.setProperty()
    //Set charachters Standings and the other shit here
    var characterid =

    scriptProperties.setProperty('RUN', true);
  }*/

  function getBp(c_string) {
    //Setup shit;
    var s = SpreadsheetApp.getActiveSheet();
    var c = SpreadsheetApp.getActiveSheet().getActiveCell();
    var row = c.getRow();
    Logger.log("Row: "+row);
    var col = c.getColumn();
    Logger.log("Col: "+col);
    //var c_string = c.getValue().toString();
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
        return json;
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

/*function getSystem(systemname) {
  // should call an api with json return: Systemid, costindex and regionid...
}*/
