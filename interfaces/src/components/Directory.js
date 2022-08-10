import { useState, useEffect, useRef } from 'react';
import ReactPaginate from 'react-paginate'
import Node from './Node.js';

function Directory(props){

  const [activePage, setActivePage] = useState(0);
  const [pageNodes, setPageNodes] = useState(null);

  const nodesPerPage = parseInt(props.settings.nodesPerPage) || 15

  const nodes = props.nodes;

  const dirRef = useRef();

  useEffect(() => {
    const start = parseInt(activePage)*nodesPerPage;
    const end = parseInt(start)+ nodesPerPage;
    setPageNodes(nodes.slice(start,end));
  }, [activePage, nodes]);

  const handlePageClick = data => {
    setActivePage(data.selected);
    dirRef.current.scrollIntoView({ behavior: 'smooth' })
  }

  var loadingDiv;

  if(!props.loaded){
    loadingDiv = <div class="mri-directory-loading"><img src={props.settings.clientPathToApp + "build/images/spinner.gif"} /></div>
  }


  return (

    <div ref={dirRef} className="mri-directory">
    {props.loaded ?
      <div className="node-list">
        <div className="node-count">{nodes.length} results found</div>
        {pageNodes.map((node) =>  <Node nodeData={node} settings={props.settings}/>)}
      </div> : loadingDiv }

     <div className="react-paginate">
        <ReactPaginate
          previousLabel={'prev'}
          nextLabel={'next'}
          breakLabel={'...'}
          breakClassName={'break-me'}
          pageCount={nodes.length/nodesPerPage}
          marginPagesDisplayed={2}
          pageRangeDisplayed={nodesPerPage}
          onPageChange={handlePageClick}
          containerClassName={'pagination'}
          subContainerClassName={'pages pagination'}
          pageClassName={'page-link-li'}
          activeClassName={'active'}
        />
      </div>
    </div>
  );
}

export default Directory
