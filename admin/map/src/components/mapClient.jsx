import L from 'leaflet'
import MarkerClusterGroup from '@changey/react-leaflet-markercluster'

import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'
import PropTypes from 'prop-types'
import { MapContainer, Marker, Popup, TileLayer } from 'react-leaflet'

import 'leaflet/dist/leaflet.css'
import '@changey/react-leaflet-markercluster/dist/styles.min.css'
import { schemaContent } from '../utils/schemaContent'

delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconUrl: iconUrl,
  iconRetinaUrl: iconRetinaUrl,
  shadowUrl: shadowUrl
})

const getPost = async (postId, apiUrl) => {
  try {
    return await fetch(`${apiUrl}/api/wp-nodes/${postId}`)
  } catch (error) {
    alert(
      `Error getting post, please contact the administrator, error: ${error}`
    )
  }
}

const markerClicked = async (profile, apiUrl, linkType) => {
  // get profile data
  const response = await getPost(profile, apiUrl)
  const responseData = await response.json()
  if (response.status !== 200) {
    alert(
      `Error getting post, please contact the administrator, error: ${responseData}`
    )
  }
  return schemaContent(responseData, linkType)
}

export default function MapClient({
  profiles,
  apiUrl,
  map,
  isMapLoaded,
  height,
  width,
  linkType
}) {
  let defaultCenter = []
  defaultCenter[0] = parseFloat(map.map_center_lat) || 48.86
  defaultCenter[1] = parseFloat(map.map_center_lon) || 2.34
  let zoom = parseInt(map.map_scale) || 5

  return (
    <div id="map">
      {isMapLoaded ? (
        <MapContainer
          style={{ height: `${height}vh`, width: `${width}vw` }}
          className="max-w-full"
          center={defaultCenter}
          zoom={zoom}
        >
          <TileLayer
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            attribution='&copy; <a target="_blank" rel="noreferrer" href="https://osm.org/copyright">OpenStreetMap</a> contributors'
          />
          <TileLayer
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            attribution='Data powered by <a target="_blank" rel="noreferrer" href="https://murmurations.network">Murmurations</a>'
          />
          <MarkerClusterGroup>
            {profiles.map(profile => (
              <Marker
                key={profile[2]}
                position={[profile[1], profile[0]]}
                eventHandlers={{
                  click: async event => {
                    // show loading while waiting for response
                    let popupInfo = event.target.getPopup()
                    popupInfo.setContent('loading from data source...')
                    const content = await markerClicked(
                      profile[2],
                      apiUrl,
                      linkType
                    )
                    popupInfo.setContent(content)
                  }
                }}
              >
                <Popup></Popup>
              </Marker>
            ))}
          </MarkerClusterGroup>
        </MapContainer>
      ) : null}
    </div>
  )
}

MapClient.propTypes = {
  profiles: PropTypes.array.isRequired,
  apiUrl: PropTypes.string.isRequired,
  map: PropTypes.object.isRequired,
  isMapLoaded: PropTypes.bool.isRequired,
  height: PropTypes.number.isRequired,
  width: PropTypes.number.isRequired,
  linkType: PropTypes.string.isRequired
}
