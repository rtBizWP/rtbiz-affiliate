<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of rtCharts
 *
 * @author faishal
 */
if ( ! class_exists ( "rtReports" ) ) {

    class rtReports {
        /**
         * 
         * 
         * */
        function draw_chart ( $title , $data , $type = "pie", $options = array(), $width= '1000', $height = '500' ) {
            $random     = rand ( 0 , 1000 ) ;
            if(!isset($options["title"]))
                $options["title"] = $title;
            if(!isset($options["is3D"]))
                $options["is3D"] = true;
            
            global $rt_chart_enqueue;
            if(!isset($rt_chart_enqueue))
                $rt_chart_enqueue = false;
            if( ! $rt_chart_enqueue ) {
            ?>
                <script type="text/javascript" src="https://www.google.com/jsapi"></script> 
            <?php } ?>
            <div id="chart_div<?php echo $random ; ?>" style="width: <?php echo $width.'px';  ?>; height: <?php echo $height.'px';  ?>;"></div>
            <div id="toolbar_div<?php echo $random ; ?>" >
            </div>
            <script type="text/javascript">
                var chart_<?php echo $random ; ?> = function() {
                    var data = google.visualization.arrayToDataTable(<?php echo json_encode ( $data ) ?>);
                    var total = 0;
                    /** for (var i = 0; i < data.getNumberOfRows(); i++) {
                        total += data.getValue(i, 1);
                    }
                    for (var i = 0; i < data.getNumberOfRows(); i++) {
                        data.setValue(i, 0, data.getValue(i, 0) + ' (' + ((data.getValue(i, 1) / total) * 100).toFixed(1) + '%)');
                    }
                    data.sort({column: 1, desc: true}) **/
                    var options = <?php echo json_encode($options) ; ?> ;
            <?php if ( $type == "column" ) { ?>
                        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div<?php echo $random ; ?>'));
            <?php }
            else if($type == "line"){ ?>
                        var chart = new google.visualization.LineChart(document.getElementById('chart_div<?php echo $random ; ?>'));
            <?php } 
            else { ?>
                        var chart = new google.visualization.PieChart(document.getElementById('chart_div<?php echo $random ; ?>'));
            <?php } ?>

                    chart.draw(data, options);
                }
                google.load("visualization", "1", {packages: ["corechart"]});
                google.setOnLoadCallback(chart_<?php echo $random ; ?> );
            </script>
            <?php
        }

    }

}
?>
