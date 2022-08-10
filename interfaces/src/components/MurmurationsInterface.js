import { useState, useEffect } from 'react';
import Form from "@rjsf/core";
import Directory from './Directory.js';
import Map from './Map.js';

function MurmurationsInterface({settings, interfaceComp}){

  const [isLoaded, setIsLoaded] = useState(false);
  const [error, setError] = useState(false);
  const [nodes, setNodes] = useState([]);
  const [search, setSearch] = useState(null);
  const [searchSubmission, setSearchSubmission] = useState(null);
  const [filterFormData, setFilterFormData] = useState(settings.formData);
  const [filterString, setFilterString] = useState(null);

  useEffect(() => {
    fetchNodes()
  }, [filterString,searchSubmission]);

  const fetchNodes = () => {
    var api_url = settings.apiUrl;
    var api_node_format = settings.apiNodeFormat;

    setIsLoaded(false);
    setNodes([]);

    var params = new URLSearchParams(filterString);

    if( interfaceComp === 'directory' ){
      params.set('format', api_node_format);
    }

    if(searchSubmission){
      params.set('search', searchSubmission);
    }

    fetch(api_url+'?'+params.toString())
      .then(res => res.json())
      .then(
        (result) => {
          setIsLoaded(true);
          setNodes(result);
        },
        (error) => {
          setIsLoaded(true);
          setError(error);
        }
      )

  }

  const handleSearchChange = (event) => {
    setSearch(event.target.value)
  }

  const handleSearchSubmit = (event) => {
    event.preventDefault();
		setSearchSubmission(search);
  }

  const handleErrors = () => {
    console.log("errors", this)
  }

  const handleFilterSubmit = ({formData}, e) => {

    var filters = "";

    setFilterFormData(formData);


    Object.keys(formData).forEach((key,index) => {
      if(formData[key]){
        if (formData[key] !== "any" && formData[key] !== ""){
          if('operator' in settings.filterSchema.properties[key]){
            var op = settings.filterSchema.properties[key].operator;
          }else{
            var op = 'equals';
          }
          filters += "filters["+index+"][0]="+key+'&';
          filters += "filters["+index+"][1]="+op+"&";
          filters += "filters["+index+"][2]="+formData[key]+"&";
        }
      }
    });

		setFilterString(filters)

  }

  const schema = settings.filterSchema;

  var interfaceComponent;

  if (error) {
    interfaceComponent = <div>Error: {error.message}</div>;
  } else {
    if (interfaceComp === 'directory' ){
      interfaceComponent = <Directory nodes={nodes} settings={settings} loaded={isLoaded} />
    } else if (interfaceComp === 'map' ){
      interfaceComponent = <Map nodes={nodes} settings={settings} loaded={isLoaded} />
    }
  }

  return (
    <div className="mri-interface">
      {settings.showFilters ?
      <div className="mri-filter-form">
        <Form schema={schema}
        formData={filterFormData}
        onChange={handleFilterSubmit}
        onError={handleErrors} />
      </div>
      : null }
      <div className="mri-content-container">
        <div className="mri-search-form">
          <form action="/" onSubmit={handleSearchSubmit} >
            <input type="text" name="search" onChange={handleSearchChange} value={search} />
            <button type="submit">Search</button>
          </form>
        </div>
        {interfaceComponent}
      </div>
    </div>
  );
}

export default MurmurationsInterface
