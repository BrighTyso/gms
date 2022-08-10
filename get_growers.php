<?php



?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>GMS</title>
</head>

<style type="text/css">
	html,body{
		margin: 0;
		padding: 0;
	}
	.grid-container {

    display: grid;
    height: 100vh;
    grid-template-columns: 100%;
    background: red;
    justify-content: center;
    /*align-content: center;*/
  }

  .grid-item-bottom{
  	height: 25vh; 
  	display: grid;
  	grid-template-columns: auto auto auto ;
  }

  .grid-item-mid{
  	height: 65vh;
  
   
  }
   .grid-item-search{
  	height: 10vh;
  	display: grid;
  	justify-content: center;
  	align-content: center;
   
  }

  
h3,h4{
    text-align: center;
  }


  table {
  border-collapse: collapse;
  width: 100%;
}


tr {
  border-bottom: 1px solid #ddd;
}


tr:nth-child(even) {
  background-color: #D6EEEE;
}

</style>
<body>

	<div class="grid-container">

		
		<div class="grid-item-search">


			<div class="search-container">

				<input type="text" name="search">

			</div>
			
		</div >

		<div class="grid-item-mid" style="background:yellow;">
			<table style="width:100%">
				<tr>
					<th>
					
					</th>
					<th>
						Name
					</th>
					<th>
						Surname
					</th>
					<th>
						Grower
					</th>
					<th>
						Phone
					</th>
					<th>
						Area
					</th>
					<th>
						Province
					</th>
					<th>
						Created At
					</th>

					<th>
						loans
					</th>
					<th>
						assessments
					</th>
				</tr>

				<tr>
					<td>
						<input type="checkbox" name="checkbox">
					</td>
					<td>
						Name
					</td>
					<td>
						Surname
					</td>
					<td>
						Grower
					</td>
					<td>
						Phone
					</td>
					<td>
						Area
					</td>
					<td>
						Province
					</td>
					<td>
						Created At
					</td>
					<td>
						loans
					</td>
					<td>
						assessments
					</td>
				</tr>

				<tr>
					<td>
						<input type="checkbox" name="checkbox">
					</td>
					<td>
						Name
					</td>
					<td>
						Surname
					</td>
					<td>
						Grower
					</td>
					<td>
						Phone
					</td>
					<td>
						Area
					</td>
					<td>
						Province
					</td>
					<td>
						Created At
					</td>
					<td>
						loans
					</td>
					<td>
						assessments
					</td>
				</tr>

				<tr>
					<td>
						<input type="checkbox" name="checkbox">
					</td>
					<td>
						Name
					</td>
					<td>
						Surname
					</td>
					<td>
						Grower
					</td>
					<td>
						Phone
					</td>
					<td>
						Area
					</td>
					<td>
						Province
					</td>
					<td>
						Created At
					</td>
					<td>
						loans
					</td>
					<td>
						assessments
					</td>
				</tr>

               <tr>
               	<td>
						<input type="checkbox" name="checkbox">
					</td>
					<td>
						Name
					</td>
					<td>
						Surname
					</td>
					<td>
						Grower
					</td>
					<td>
						Phone
					</td>
					<td>
						Area
					</td>
					<td>
						Province
					</td>
					<td>
						Created At
					</td>
					<td>
						loans
					</td>
					<td>
						assessments
					</td>
				</tr>


				<tr>
					<td>
						<input type="checkbox" name="checkbox">
					</td>
					<td>
						Name
					</td>
					<td>
						Surname
					</td>
					<td>
						Grower
					</td>
					<td>
						Phone
					</td>
					<td>
						Area
					</td>
					<td>
						Province
					</td>
					<td>
						Created At
					</td>
					<td>
						loans
					</td>
					<td>
						assessments
					</td>
				</tr>


				<tr>
					<td>
						<input type="checkbox" name="checkbox">
					</td>
					<td>
						Name
					</td>
					<td>
						Surname
					</td>
					<td>
						Grower
					</td>
					<td>
						Phone
					</td>
					<td>
						Area
					</td>
					<td>
						Province
					</td>
					<td>
						Created At
					</td>
					<td>
						loans
					</td>
					<td>
						assessments
					</td>
				</tr>


				<tr>
					<td>
						<input type="checkbox" name="checkbox">
					</td>
					<td>
						Name
					</td>
					<td>
						Surname
					</td>
					<td>
						Grower
					</td>
					<td>
						Phone
					</td>
					<td>
						Area
					</td>
					<td>
						Province
					</td>
					<td>
						Created At
					</td>
					<td>
						<a href="#" onclick="console.log(1+1)">loans</a>
					</td>
					<td>
						<a href="#">assessments</a>
						
					</td>
				</tr>

			</table>
			

		</div>

		<div id="loansview" class="grid-item-bottom" style="background: green;">

			<div class="grid-item" style="background:blue">

				<table>
					<tr>
						<th>
							
						</th>
						<th>
							Product
						</th>
						<th>
							Units
						</th>
						<th>
							Quantity
						</th>
						<th>
							Date
						</th>

					</tr>
					<tr>
						<td><input type="checkbox" name="checkbox"></td>
						<td>sadza</td>
						<td>kgs</td>
						<td>1</td>
						<td>2022-08-12</td>
						
					</tr>

					<tr>
						<td><input type="checkbox" name="checkbox"></td>
						<td>sadza</td>
						<td>kgs</td>
						<td>1</td>
						<td>2022-08-12</td>
						
					</tr>

					<tr>
						<td><input type="checkbox" name="checkbox"></td>
						<td>sadza</td>
						<td>kgs</td>
						<td>1</td>
						<td>2022-08-12</td>
						
					</tr>
				</table>

			</div>

			<div class="grid-item" style="background:gray;display: grid;grid-template-columns: auto auto auto;">

				<form>
					<label>Product</label>
				<div><input type="text" name="text"></div>
				<label>Units</label>
				<div><input type="text" name="text"></div>
				<label>Quantity</label>
				<div><input type="text" name="text"></div>
				<br>
				<input type="submit" name="submit">

				</form>

				<form>
				<label>Product</label>
				<div><input type="text" name="text"></div>
				<label>Units</label>
				<div><input type="text" name="text"></div>
				<label>Quantity</label>
				<div><input type="text" name="text"></div>
				<br>
				<input type="submit" name="submit">
				</form>

				<div>
					<div>
						<input type="submit" name="submit" value="Loan statement">
					</div>

					<div>
						<input type="submit" name="submit" value="Input statement">
					</div>
					
				</div>
				
			</div>

			<div class="grid-item" style="background:pink;">
				
				
			</div>
			

		</div>



		<div id="assessmentview" class="grid-item-bottom" style="background: green; display: none;">

			<div class="grid-item" style="background:blue">
				

			</div>

			<div class="grid-item" style="background:gray">
				
			</div>

			<div class="grid-item" style="background:pink;">
				
				
			</div>
			

		</div>


	</div>

</body>
<script type="text/javascript">
	

</script>
</html>