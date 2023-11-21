import L from 'leaflet'
import MarkerClusterGroup from '@changey/react-leaflet-markercluster'

import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'
import PropTypes from 'prop-types'
import { MapContainer, Marker, Popup, TileLayer } from 'react-leaflet'

import 'leaflet/dist/leaflet.css'
import '@changey/react-leaflet-markercluster/dist/styles.min.css'

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
  let content = ''
  const title = responseData?.title
  if (title) {
    content += `<strong>${title}</strong>`
  }
  const description = responseData?.profile_data?.description
  if (description) {
    content += `<p>${limitString(description, 100)}</p>`
  }
  const postUrl =
    linkType === 'wp'
      ? responseData.post_url
      : responseData?.profile_data?.primary_url
  if (postUrl) {
    content += `<p>More: <a target='_blank' rel='noreferrer' href='${postUrl}'>${postUrl}</a></p>`
  }
  const imageUrl = responseData?.profile_data?.image
  if (imageUrl) {
    content += `<img src='${imageUrl}' alt='profile image' width='100' height='100' id="profile_image" />`

    const img = new Image()
    img.src = imageUrl
    img.onerror = () => {
      const profileImage = document.getElementById('profile_image')
      if (profileImage) {
        profileImage.style.display = 'none'
      }
    }
  }
  return content
}

function limitString(inputString, maxLength) {
  if (inputString.length <= maxLength) {
    return inputString
  } else {
    return inputString.substring(0, maxLength) + '...'
  }
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
  defaultCenter[0] = parseFloat(map.map_center_lat) || 48.864716
  defaultCenter[1] = parseFloat(map.map_center_lon) || 2.349014
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
            attribution='&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
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
