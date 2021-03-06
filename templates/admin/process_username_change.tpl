<div class="hms">
  <div class="box">
    <div class="title"> <h1>User Name Change Results</h1></div>
    <div class="box-content">
        <!-- BEGIN error_msg -->
        <span class="error">{ERROR_MSG}<br /></span>
        <!-- END error_msg -->
        
        <!-- BEGIN success_msg -->
        <span class="success">{SUCCESS_MSG}<br /></span>
        <!-- END success_msg -->
        Results:
        <table cellspacing="2" cellpadding="2">
          <tr>
            <th>User name</th>
            <th>Message</th>
          </tr>
          <!-- BEGIN status -->
          <tr>
            <td>{USERNAME}</td>
            <td>{MESSAGE}</td>
          </tr>
          <!-- END status -->
        </table>
        <br />
        <h2>Warnings:</h2>
        <table>
            <tr>
                <th>User name</th>
                <th>Message</th>
            </tr>
            <!-- BEGIN warnings -->
            <tr>
                <td>{USERNAME}</td>
                <td>{MESSAGE}</td>
            </tr>
            <!-- END warnings -->
        </table>
        <br />Done.<br />
    </div>
  </div>
</div>
