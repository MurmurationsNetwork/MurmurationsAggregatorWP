import MapSettings from './MapSettings'
import { updateCustomMap } from '../utils/api'
import { formDefaults } from '../data/formDefaults'
import PropTypes from 'prop-types'

export default function EditData({
  formData,
  handleInputChange,
  setIsLoading,
  setIsMapSelected,
  setIsEdit,
  setFormData,
  getMaps,
  isLoading
}) {
  const handleEditSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      const response = await updateCustomMap(
        formData.map_id,
        formData.map_name,
        formData.map_center_lat,
        formData.map_center_lon,
        formData.map_scale
      )

      if (!response.ok) {
        const responseData = await response.json()
        alert(`Map Error: ${response.status} ${JSON.stringify(responseData)}`)
      }
    } catch (error) {
      setIsMapSelected(false)
      alert(`Edit map error: ${error}`)
    } finally {
      setIsMapSelected(false)
      setIsEdit(false)
      setIsLoading(false)
      setFormData(formDefaults)
      await getMaps()
    }
  }

  const handleCancel = () => {
    setIsMapSelected(false)
    setIsEdit(false)
    window.scrollTo(0, 0)
  }

  return (
    <div>
      <h2 className="text-2xl">Edit Map</h2>
      <form onSubmit={handleEditSubmit} className="py-6">
        <MapSettings
          formData={formData}
          handleInputChange={handleInputChange}
        />
        <input type="hidden" name="map_id" value={formData.map_id} />
        <div className="mt-6">
          <button
            type="submit"
            className={`mx-4 rounded-full bg-orange-500 px-4 py-2 text-lg font-bold text-white hover:scale-110 hover:bg-orange-400 active:scale-90 disabled:opacity-75 ${
              isLoading ? 'cursor-not-allowed opacity-50' : ''
            }`}
          >
            {isLoading ? 'Submitting...' : 'Submit'}
          </button>
          <button
            onClick={handleCancel}
            className="mx-4 rounded-full bg-gray-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-gray-400 active:scale-90 disabled:opacity-75"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  )
}

EditData.propTypes = {
  formData: PropTypes.object.isRequired,
  handleInputChange: PropTypes.func.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  setIsEdit: PropTypes.func.isRequired,
  setIsMapSelected: PropTypes.func.isRequired,
  setFormData: PropTypes.func.isRequired,
  getMaps: PropTypes.func.isRequired,
  isLoading: PropTypes.bool.isRequired
}
