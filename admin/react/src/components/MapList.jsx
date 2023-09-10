import { compareWithWpNodes, deleteWpMap } from '../utils/api'
import PropTypes from 'prop-types'

export default function MapList({
  apiUrl,
  maps,
  getMaps,
  setFormData,
  setIsEdit,
  isLoading,
  setIsLoading,
  setIsRetrieving,
  setProfileList,
  setTagSlug
}) {
  const handleRetrieve = async (map_id, request_url, tag_slug) => {
    setIsLoading(true)
    setIsRetrieving(true)

    try {
      // get data from request_url - Index URL + Query URL
      const response = await fetch(request_url)
      const responseData = await response.json()
      if (!response.ok) {
        alert(
          `Retrieve Error: ${response.status} ${JSON.stringify(responseData)}`
        )
        return
      }

      // check with wpdb
      const profiles = responseData.data
      const dataWithIds = []
      let current_id = 1
      for (let profile of profiles) {
        let profile_data = ''
        if (profile.profile_url) {
          const response = await fetch(profile.profile_url)
          if (response.ok) {
            profile_data = await response.json()
          }
        }
        // give extra data to profile
        profile.id = current_id
        profile.profile_data = profile_data
        profile.tag_slug = tag_slug
        profile.map_id = map_id

        // compare with wpdb
        const profileResponse = await compareWithWpNodes(
          apiUrl,
          map_id,
          profile_data,
          profile.profile_url
        )
        const profileResponseData = await profileResponse.json()

        if (!profileResponse.ok && profileResponse.status !== 404) {
          alert(
            `Retrieve Error: ${profileResponse.status} ${JSON.stringify(
              profileResponseData
            )}`
          )
          return
        }

        if (profileResponse.status === 404) {
          profile.status = 'new'
        } else {
          // if the profile is ignored, don't show up again
          if (profileResponseData.status === 'ignore') {
            continue
          }

          profile.status = profileResponseData.status
          if (profileResponseData.has_update) {
            profile.extra_notes = 'see updates'
          }
        }

        if (profile_data === '') {
          profile.extra_notes = 'unavailable'
        }

        current_id++
        dataWithIds.push(profile)
      }
      setProfileList(dataWithIds)
    } catch (error) {
      alert(`Retrieve node error: ${JSON.stringify(error)}`)
    } finally {
      setIsLoading(false)
    }
  }

  const handleEdit = async tag_slug => {
    setIsEdit(true)
    setProfileList([])
    const map = maps.find(map => map.tag_slug === tag_slug)
    setFormData({
      map_name: map.name,
      map_center_lat: map.map_center_lat,
      map_center_lon: map.map_center_lon,
      map_scale: map.map_scale
    })
    setTagSlug(map.tag_slug)
  }

  const handleDelete = async map_id => {
    setIsLoading(true)

    try {
      const mapResponse = await deleteWpMap(apiUrl, map_id)
      if (!mapResponse.ok) {
        const mapResponseData = await mapResponse.json()
        alert(
          `Map Error: ${mapResponse.status} ${JSON.stringify(mapResponseData)}`
        )
      }
    } catch (error) {
      alert(`Delete map error: ${JSON.stringify(error)}`)
    } finally {
      setIsLoading(false)
      await getMaps()
    }
  }

  return (
    <div>
      <h2 className="text-xl">Map Data</h2>
      {maps.length > 0 ? (
        maps.map((map, index) => (
          <div className="bg-white p-4 rounded shadow-md mt-4" key={index}>
            <h2 className="text-xl font-semibold mb-2">{map.name}</h2>
            <p>
              <strong>Index URL:</strong> {map.index_url}
            </p>
            <p>
              <strong>Query URL:</strong> {map.query_url}
            </p>
            <p>
              <strong>Tag Slug:</strong> {map.tag_slug}
            </p>
            <p>
              <strong>Map Center:</strong>{' '}
              {map.map_center_lon + ',' + map.map_center_lat}
            </p>
            <p>
              <strong>Map Scale:</strong> {map.map_scale}
            </p>
            <p>
              <strong>Created At:</strong> {map.created_at}
            </p>
            <p>
              <strong>Updated At:</strong> {map.updated_at}
            </p>
            <div className="box-border flex flex-wrap xl:min-w-max flex-row mt-4 justify-between">
              <button
                className={`my-1 mx-2 max-w-fit rounded-full bg-amber-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-yellow-400 disabled:opacity-75 ${
                  isLoading ? 'opacity-50 cursor-not-allowed' : ''
                }`}
                onClick={() =>
                  handleRetrieve(
                    map.id,
                    map.index_url + map.query_url,
                    map.tag_slug
                  )
                }
              >
                {isLoading ? 'Loading' : 'Retrieve'}
              </button>
              <button
                className="my-1 mx-2 max-w-fit rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75"
                onClick={() => handleEdit(map.tag_slug)}
              >
                Edit
              </button>
              <button
                className={`my-1 mx-2 max-w-fit rounded-full bg-red-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-red-400 disabled:opacity-75 ${
                  isLoading ? 'opacity-50 cursor-not-allowed' : ''
                }`}
                onClick={() => handleDelete(map.id)}
              >
                {isLoading ? 'Loading' : 'Delete'}
              </button>
            </div>
          </div>
        ))
      ) : (
        <p>No maps found.</p>
      )}
    </div>
  )
}

MapList.propTypes = {
  apiUrl: PropTypes.string.isRequired,
  maps: PropTypes.array.isRequired,
  getMaps: PropTypes.func.isRequired,
  setFormData: PropTypes.func.isRequired,
  setIsEdit: PropTypes.func.isRequired,
  isLoading: PropTypes.bool.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  setIsRetrieving: PropTypes.func.isRequired,
  setProfileList: PropTypes.func.isRequired,
  setTagSlug: PropTypes.func.isRequired
}