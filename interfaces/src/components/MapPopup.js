const MapPopup = ({node}) => {
  return (
  <div>
    {node.image &&
      <img
        src={node.image[0].url || node.image}
        onError={({ currentTarget }) => {
          currentTarget.style.display = "none"
        }}
        alt={node.name+" image"}
        maxWidth={"50%"}
        height={8}
      />
    }
    <div>
        {
          node.url || node.urls || node.primary_url ?
            <a href={node.primary_url || node.url || node.urls[0].url}  target="_blank" rel="noopener noreferrer">
              <span wordBreak="break-all">{node.name}</span>
            </a>
              :
            <span wordBreak="break-all">{node.name}</span>
        }
    </div>
    {node.description &&
      <div>
          {node.description.length > 250 ? `${node.description.slice(0,250)}...`: node.description}
      </div>
    }
  </div>
  )
}

export default MapPopup
